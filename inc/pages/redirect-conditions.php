<?php
function plugin_handle_redirects()
{

    if (current_user_can('administrator') || is_front_page()) {
        return;
    }

    $post_id = get_queried_object_id();
    $is_exception = get_post_meta($post_id, 'exception_redirection', true);
    $unauthorized_redirect = get_option('unauthorized_redirect');
    $no_subscription_redirect = get_option('no_subscription_redirect');

    if ($is_exception) {
        return;
    }

    if ($post_id == $unauthorized_redirect || $post_id == $no_subscription_redirect) {
        return;
    }

    if (is_user_logged_in()) {
        $user_has_subscription = true; // Замените эту строку проверкой подписки пользователя
        $no_subscription_redirect = get_option('no_subscription_redirect');

        if (!$user_has_subscription && $no_subscription_redirect) {
            $no_subscription_redirect_url = get_permalink($no_subscription_redirect);

            if ($no_subscription_redirect_url) {
                setcookie('redirected', '1', time() + 3600, '/');
                wp_redirect($no_subscription_redirect_url);
                exit;
            }
        }
    } else {
        $unauthorized_redirect = get_option('unauthorized_redirect');

        if ($unauthorized_redirect) {
            $unauthorized_redirect_url = get_permalink($unauthorized_redirect);

            if ($unauthorized_redirect_url) {
                setcookie('redirected', '1', time() + 3600, '/');
                wp_redirect($unauthorized_redirect_url);
                exit;
            }
        }
    }
}

add_action('wp', 'plugin_handle_redirects');


function plugin_enqueue_scripts()
{
    if (!is_user_logged_in() || !user_has_subscription()) {
        wp_enqueue_style('popup', plugin_dir_url(__DIR__) . 'src/css/popup.css', array(), '1.0.0');
        wp_enqueue_script('popup', plugin_dir_url(__DIR__) . 'src/js/popup.js', array('jquery'), '1.0.0', true);
        wp_localize_script('popup', 'popupParams', array(
            'userLoggedIn' => is_user_logged_in(),
            'userHasSubscription' => user_has_subscription(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ));
    }
}

add_action('wp_enqueue_scripts', 'plugin_enqueue_scripts');

function plugin_display_popup()
{
    if (!is_user_logged_in() || !user_has_subscription()) {
        ?>
        <div id="popup" class="popup" style="display:none;">
            <div class="popup-inner">
                <h2><?php echo __('To continue, you need to subscribe', 'checkintravel'); ?></h2>
                <?php if (!is_user_logged_in()) { ?> <p><?php echo __('Enter your email:', 'checkintravel'); ?></p>
                    <input type="email" name="email"
                           placeholder="<?php echo __('example@mail.com', 'checkintravel'); ?>">
                <?php } ?>
                <button id="subscribe-btn"><?php echo __('Subscribe', 'checkintravel'); ?></button>
                <p><?php echo __('Already have an account?', 'checkintravel'); ?> <a
                            href="<?php echo wp_login_url(); ?>"><?php echo __('Log in', 'checkintravel'); ?></a></p>
            </div>
        </div>
        <?php
    }
}

add_action('wp_footer', 'plugin_display_popup');


function user_has_subscription()
{
    return true;
}


add_action('wp_ajax_create_order', 'create_order_callback');
add_action('wp_ajax_nopriv_create_order', 'create_order_callback');

function create_order_callback()
{
    $email = $_POST['email'];
    $user = get_user_by('email', $email);

    // Шаг 1: Проверяем, существует ли пользователь с указанным email в базе данных.
    if ($user) {
        // Пользователь существует, предложить авторизацию на сайте или ввести другой email.
        wp_send_json_error('User already exists');
    }

    // Шаг 2: Если пользователь не существует, создать учетную запись только с email.
    $password = wp_generate_password();
    $user_id = wp_create_user($email, $password, $email);
    $token = wp_generate_password(32, false);
    $expiration_time = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    add_user_meta($user_id, 'auto_login_token', $token);
    add_user_meta($user_id, 'auto_login_expiration_time', $expiration_time);
    if (!$user_id) {
        wp_send_json_error('Failed to create user');
    }

    // Шаг 3: Создать заказ с указанным email и добавить его в базу данных.
    $order = array(
        'email' => $email,
        // Другие данные заказа
    );
    $order_id = wp_insert_post(array(
        'post_title' => 'Order #' . uniqid(),
        'post_type' => 'order',
        'post_status' => 'pending',
        'meta_input' => array(
            '_order_data' => $order,
        ),
    ));
    if (!$order_id) {
        wp_send_json_error('Failed to create order');
    }

    // Шаг 4: Создать оплату в монобанке и записать данные об оплате в заказ.
    $payment = create_monobank_payment($order_id, $token);
    if (!$payment) {
        wp_send_json_error('Failed to create payment');
    }
    update_post_meta($order_id, '_payment_data', $payment);

    // Шаг 5: Вернуть ссылку на страницу оплаты.
    if (!isset($payment->pageUrl) || $payment->pageUrl == '') {
        wp_send_json_error('Что-то пошло не так обратитесь в поддержку');
    }

    wp_send_json_success(array('payment_url' => $payment->pageUrl));
}

function create_monobank_payment($order_id, $token = false)
{
    $curl = curl_init();

    $url = 'https://api.monobank.ua/api/merchant/invoice/create';

    $headers = array(
        'X-Token: ' . get_option('monobank_token'),
        'Content-Type: application/json'
    );

    $tariff_duration = get_option('tariff_duration', 0);
    $tariff_duration_type = get_option('tariff_duration_type', 'day');

    if ($tariff_duration_type === 'day') {
        $expires_at = strtotime('+' . $tariff_duration . ' days');
    } elseif ($tariff_duration_type === 'week') {
        $expires_at = strtotime('+' . $tariff_duration . ' weeks');
    } elseif ($tariff_duration_type === 'month') {
        $expires_at = strtotime('+' . $tariff_duration . ' months');
    } elseif ($tariff_duration_type === 'year') {
        $expires_at = strtotime('+' . $tariff_duration . ' years');
    }
    $callbackUrl = get_permalink(get_option('login_page', 0));
    if ($token) {
        $callbackUrl .= "?token=$token";
    }
    $data = array(
        'amount' => (int)(get_option('tariff_cost', 0) * 100), // умножаем на 100, чтобы получить сумму в копейках
        'currencyCode' => 840,
        'description' => 'Subscription (' . get_bloginfo('name') . ')',
        'details' => get_option('tariff_duration', 0) . ' ' . get_option('tariff_duration_type', 'day'),
        'merchantInvoiceId' => $order_id,
        'expiresAt' => date('c', $expires_at),
        'callbackUrl' => $callbackUrl,
        'successUrl' => site_url('/monobank_success?order_id=' . $order_id),
        'failureUrl' => site_url('/monobank_failure?order_id=' . $order_id),
    );

    $body = json_encode($data);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);

    curl_close($curl);

    if ($response === false) {
        return false;
    } else {
        $result = json_decode($response);

        if (isset($result->errorDescription)) {
            return false;
        } else {
            return $result;
        }
    }
}


function auto_login_from_token()
{
    $login_page_id = get_option('login_page', 0);
    if (is_page($login_page_id) && !is_user_logged_in()) {
        // Если в GET-параметрах есть токен, ищем пользователя с таким токеном и авторизуем его
        if (isset($_GET['token'])) {
            $auto_login_token = sanitize_text_field($_GET['token']);
            $user = get_users(array(
                'meta_key' => 'auto_login_token',
                'meta_value' => $auto_login_token,
                'meta_compare' => '=',
                'number' => 1,
                'count_total' => false,
                'fields' => 'all',
            ));
            if (!empty($user)) {
                $user = $user[0];
                $expiration_time = get_user_meta($user->ID, 'auto_login_expiration_time', true);
                if ($expiration_time > time()) {
                    wp_set_auth_cookie($user->ID);
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
    }
}

add_action('wp_loaded', 'auto_login_from_token');
<?php
function plugin_monopay_settings_page()
{
    // Проверяем права доступа пользователя
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Выводим содержимое страницы настроек
    ?>
    <div class="wrap">
        <h1><?php echo __('Monopay Settings', 'checkintravel'); ?></h1>
        <?php settings_errors('monobank_credentials'); ?>
        <form method="post" action="options.php">
            <?php settings_fields('plugin_monopay_settings'); ?>
            <?php do_settings_sections('plugin_monopay_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo __('Monobank Public Key', 'checkintravel'); ?></th>
                    <td><input type="text" name="monobank_token"
                               value="<?php echo esc_attr(get_option('monobank_token')); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __('Monobank Test Mode', 'checkintravel'); ?></th>
                    <td><input type="checkbox" name="monobank_test_mode"
                               value="1" <?php checked(1, get_option('monobank_test_mode'), true); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __('Monobank Success URL', 'checkintravel'); ?></th>
                    <td><input type="text" name="monobank_success_url"
                               value="<?php echo esc_attr(get_option('monobank_success_url')); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __('Monobank Failure URL', 'checkintravel'); ?></th>
                    <td><input type="text" name="monobank_failure_url"
                               value="<?php echo esc_attr(get_option('monobank_failure_url')); ?>"/></td>
                </tr>
            </table>
            <p><?php printf(__('To obtain Monobank Public Key, please follow the instructions in the <a href="%s" target="_blank">Monobank documentation</a>.', 'checkintravel'), 'https://api.monobank.ua/docs/#tag/Publichni-dani'); ?></p>
            <?php submit_button(__('Save Changes', 'checkintravel')); ?>
        </form>
    </div>
    <?php
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
    $data = array(
        'amount' => (int)(get_option('tariff_cost', 0) * 100), // умножаем на 100, чтобы получить сумму в копейках
        'currencyCode' => 840,
        'description' => 'Subscription (' . get_bloginfo('name') . ')',
        'details' => get_option('tariff_duration', 0) . ' ' . get_option('tariff_duration_type', 'day'),
        'merchantInvoiceId' => 'INV' . rand(10000, 99999),
        'expiresAt' => date('c', strtotime('+1 day')),
        'callbackUrl' => 'https://example.com/callback',
        'successUrl' => 'https://example.com/success',
        'failureUrl' => 'https://example.com/failure',
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
        echo '<div class="notice notice-error is-dismissible"><p>';
        echo __('Error sending request to Monobank API', 'checkintravel');
        echo '</p></div>';
    } else {
        $result = json_decode($response);

        if (isset($result->errorDescription)) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo __('Monobank API error: ', 'checkintravel') . $result->errorDescription;
            echo '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo __('Monobank API response: ', 'checkintravel') . $response;
            echo '</p></div>';
        }
    }

}



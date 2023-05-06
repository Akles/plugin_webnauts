<?php

function plugin_tariff_settings_page() {
    echo __('Tariff Settings Page', 'checkintravel');
    ?>
    <div class="wrap">
        <h1><?php echo __('Redirect Settings', 'checkintravel'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('tariff_duration_group');
            do_settings_sections('tariff_duration_group');

            plugin_render_tariff_duration();
            plugin_render_tariff_cost();
            submit_button();
            ?>

        </form>
    </div>
    <?php

}

function plugin_render_tariff_duration() {
    $tariff_duration = get_option('tariff_duration', 0);
    $tariff_duration_type = get_option('tariff_duration_type', 'day');
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __('Длительность тарифа', 'checkintravel'); ?></th>
            <td>
                <input type="number" name="tariff_duration"  step="1"  value="<?php echo $tariff_duration; ?>" min="0">
                <select name="tariff_duration_type">
                    <option value="day" <?php selected($tariff_duration_type, 'day'); ?>><?php echo __('День', 'checkintravel'); ?></option>
                    <option value="week" <?php selected($tariff_duration_type, 'week'); ?>><?php echo __('Неделя', 'checkintravel'); ?></option>
                    <option value="month" <?php selected($tariff_duration_type, 'month'); ?>><?php echo __('Месяц', 'checkintravel'); ?></option>
                    <option value="year" <?php selected($tariff_duration_type, 'year'); ?>><?php echo __('Год', 'checkintravel'); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}


function plugin_render_tariff_cost() {
    $tariff_cost = get_option('tariff_cost', 0);
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __('Стоимость тарифа', 'checkintravel'); ?></th>
            <td>
                <input type="number" step="1" name="tariff_cost" value="<?php echo $tariff_cost; ?>" min="0">
            </td>
        </tr>
    </table>
    <?php
}
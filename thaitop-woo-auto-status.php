<?php
/*
Plugin Name: ThaiTop Woo Auto Status
Description: Auto change WooCommerce order status after specified time
Version: 1.0.0
Author: ThaiTop
*/

defined('ABSPATH') or die('No script kiddies please!');

// Add menu
add_action('admin_menu', 'twas_add_admin_menu');
function twas_add_admin_menu() {
    add_menu_page(
        'Order Auto Status Settings',
        'Order Auto Status',
        'manage_options',
        'order-auto-status',
        'twas_settings_page',
        'dashicons-clock'  // เพิ่ม icon นาฬิกา
    );
    
    add_submenu_page(
        'order-auto-status',
        'Debug Tools',
        'Debug Tools',
        'manage_options',
        'order-auto-status-debug',
        'twas_debug_page'
    );
}

// Add debug page
function twas_debug_page() {
    // Check for clear log action
    if (isset($_POST['twas_clear_log']) && check_admin_referer('twas_clear_log', 'twas_clear_nonce')) {
        update_option('twas_debug_log', array());
        echo '<div class="notice notice-success"><p>Debug log cleared successfully.</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>Debug Tools</h1>
        
        <!-- Debug Tools -->
        <div class="card" style="max-width: 100%;">
            <h2>Manual Trigger</h2>
            <form method="post">
                <?php wp_nonce_field('twas_test_update', 'twas_nonce'); ?>
                <p>
                    <input type="submit" name="twas_test_update" class="button" value="Run Status Update Now">
                    <br><br>
                    Next scheduled run: <?php 
                        $next_run = wp_next_scheduled('twas_check_orders');
                        if ($next_run) {
                            $datetime = new DateTime();
                            $datetime->setTimestamp($next_run);
                            $datetime->setTimezone(new DateTimeZone(wp_timezone_string()));
                            echo $datetime->format('Y-m-d H:i:s') . ' (' . wp_timezone_string() . ')';
                        } else {
                            echo 'Not scheduled';
                        }
                    ?>
                </p>
            </form>
        </div>

        <!-- Debug Log -->
        <div class="card" style="max-width: 100%; margin-top: 20px;">
            <h2>Debug Log</h2>
            <form method="post" style="margin-bottom: 10px;">
                <?php wp_nonce_field('twas_clear_log', 'twas_clear_nonce'); ?>
                <input type="submit" name="twas_clear_log" class="button" value="Clear Log" onclick="return confirm('Are you sure you want to clear the debug log?');">
            </form>
            <pre style="background: #f0f0f0; padding: 10px; overflow: auto; max-height: 400px;"><?php
                $log = get_option('twas_debug_log', array());
                $log = array_slice($log, -50); // Show last 50 entries
                $log = array_reverse($log); // เรียงลำดับใหม่ให้ log ล่าสุดอยู่บน
                foreach ($log as $entry) {
                    echo esc_html($entry) . "\n";
                }
            ?></pre>
        </div>
    </div>
    <?php
    
    // Check for manual trigger
    if (isset($_POST['twas_test_update'])) {
        twas_update_order_status(true);
        echo '<div class="notice notice-info"><p>Manual status update triggered.</p></div>';
    }
}

// Create settings page
function twas_settings_page() {
    // Check for manual trigger
    if (isset($_POST['twas_test_update'])) {
        twas_update_order_status(true);
        echo '<div class="notice notice-info"><p>Manual status update triggered.</p></div>';
    }

    if (isset($_POST['twas_save_settings'])) {
        $status_rules = array();
        $from_statuses = $_POST['from_status'] ?? array();
        $to_statuses = $_POST['to_status'] ?? array();
        $times = $_POST['time'] ?? array();
        $time_units = $_POST['time_unit'] ?? array();
        
        for ($i = 0; $i < count($from_statuses); $i++) {
            if (!empty($from_statuses[$i]) && !empty($to_statuses[$i]) && !empty($times[$i])) {
                $status_rules[] = array(
                    'from' => sanitize_text_field($from_statuses[$i]),
                    'to' => sanitize_text_field($to_statuses[$i]),
                    'time' => intval($times[$i]),
                    'unit' => sanitize_text_field($time_units[$i])
                );
            }
        }
        
        update_option('twas_status_rules', $status_rules);
        echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
    }
    
    $status_rules = get_option('twas_status_rules', array(
        array('from' => 'pending', 'to' => 'processing', 'time' => 24, 'unit' => 'hours'),
        array('from' => 'processing', 'to' => 'completed', 'time' => 48, 'unit' => 'hours')
    ));
    
    $wc_statuses = wc_get_order_statuses();
    ?>
    <div class="wrap">
        <h1>Order Auto Status Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Status Rules</th>
                    <td>
                        <div id="status-rules">
                            <?php foreach ($status_rules as $rule): ?>
                            <div class="status-rule" style="margin-bottom: 10px;">
                                <select name="from_status[]">
                                    <?php foreach ($wc_statuses as $status_key => $status_label): 
                                        $status_key = str_replace('wc-', '', $status_key);
                                    ?>
                                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($status_key, $rule['from']); ?>>
                                            <?php echo esc_html($status_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                →
                                <select name="to_status[]">
                                    <?php foreach ($wc_statuses as $status_key => $status_label): 
                                        $status_key = str_replace('wc-', '', $status_key);
                                    ?>
                                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($status_key, $rule['to']); ?>>
                                            <?php echo esc_html($status_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                after 
                                <input type="number" name="time[]" value="<?php echo esc_attr($rule['time']); ?>" min="1">
                                <select name="time_unit[]">
                                    <option value="minutes" <?php selected($rule['unit'], 'minutes'); ?>>Minutes</option>
                                    <option value="hours" <?php selected($rule['unit'], 'hours'); ?>>Hours</option>
                                </select>
                                <button type="button" class="button remove-rule">Remove</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button add-rule">Add New Rule</button>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="twas_save_settings" class="button button-primary" value="Save Settings">
            </p>
        </form>

        <h2 style="margin-top: 30px;">Orders Being Monitored</h2>
        <div class="order-monitor-table">
            <?php
            $status_rules = get_option('twas_status_rules', array());
            $status_array = array_column($status_rules, 'from');
            
            $args = array(
                'limit' => -1,
                'status' => $status_array
            );
            
            $orders = wc_get_orders($args);
            
            if (!empty($orders)) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Current Status</th>
                            <th>Created</th>
                            <th>Time Passed</th>
                            <th>Next Status</th>
                            <th>Change In</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($orders as $order) {
                            $order_date = $order->get_date_created();
                            $current_status = $order->get_status();
                            $minutes_passed = (time() - strtotime($order_date)) / 60;
                            
                            // Find applicable rule
                            $next_status = '';
                            $remaining_time = '';
                            foreach ($status_rules as $rule) {
                                if ($current_status === $rule['from']) {
                                    $next_status = $rule['to'];
                                    $required_minutes = ($rule['unit'] === 'hours') ? 
                                                      $rule['time'] * 60 : 
                                                      $rule['time'];
                                    $remaining_minutes = $required_minutes - $minutes_passed;
                                    
                                    if ($remaining_minutes > 60) {
                                        $remaining_time = round($remaining_minutes / 60, 1) . ' hours';
                                    } else {
                                        $remaining_time = round($remaining_minutes) . ' minutes';
                                    }
                                    break;
                                }
                            }
                            
                            $time_passed = '';
                            if ($minutes_passed > 60) {
                                $time_passed = round($minutes_passed / 60, 1) . ' hours';
                            } else {
                                $time_passed = round($minutes_passed) . ' minutes';
                            }
                            ?>
                            <tr>
                                <td><a href="<?php echo $order->get_edit_order_url(); ?>" target="_blank">#<?php echo $order->get_id(); ?></a></td>
                                <td><?php echo wc_get_order_status_name($current_status); ?></td>
                                <td><?php echo $order_date->date('Y-m-d H:i:s'); ?></td>
                                <td><?php echo $time_passed; ?></td>
                                <td><?php echo wc_get_order_status_name($next_status); ?></td>
                                <td><?php echo $remaining_time; ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>No orders are currently being monitored.</p>';
            }
            ?>
        </div>
    </div>

    <style>
    .order-monitor-table {
        margin-top: 15px;
    }
    .order-monitor-table table {
        border-spacing: 0;
    }
    .order-monitor-table th,
    .order-monitor-table td {
        padding: 8px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        var ruleTemplate = $('#status-rules .status-rule').first().clone();
        
        $('.add-rule').click(function() {
            var newRule = ruleTemplate.clone();
            newRule.find('input[name="time[]"]').val('60');
            newRule.find('select[name="time_unit[]"]').val('minutes');
            $('#status-rules').append(newRule);
        });
        
        $(document).on('click', '.remove-rule', function() {
            if ($('.status-rule').length > 1) {
                $(this).closest('.status-rule').remove();
            }
        });
    });
    </script>
    <?php
}

// Add custom cron schedule
add_filter('cron_schedules', 'twas_add_cron_interval');
function twas_add_cron_interval($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60, // 1 minute in seconds
        'display'  => 'Every Minute'
    );
    return $schedules;
}

// Schedule cron job
register_activation_hook(__FILE__, 'twas_activation');
function twas_activation() {
    if (!wp_next_scheduled('twas_check_orders')) {
        wp_schedule_event(time(), 'every_minute', 'twas_check_orders');
    }
}

register_deactivation_hook(__FILE__, 'twas_deactivation');
function twas_deactivation() {
    wp_clear_scheduled_hook('twas_check_orders');
}

// Add logging function
function twas_log($message) {
    $log = get_option('twas_debug_log', array());
    $datetime = new DateTime('now', new DateTimeZone(wp_timezone_string()));
    $log[] = $datetime->format('Y-m-d H:i:s') . ' - ' . $message;
    $log = array_slice($log, -100); // Keep only last 100 entries
    update_option('twas_debug_log', $log);
}

// Check and update orders
add_action('twas_check_orders', 'twas_update_order_status');
function twas_update_order_status($is_manual = false) {
    twas_log('Starting order status update' . ($is_manual ? ' (manual trigger)' : ''));
    
    $status_rules = get_option('twas_status_rules', array(
        array('from' => 'pending', 'to' => 'processing', 'time' => 24, 'unit' => 'hours'),
        array('from' => 'processing', 'to' => 'completed', 'time' => 48, 'unit' => 'hours')
    ));
    
    $status_array = array_column($status_rules, 'from');
    twas_log('Checking orders with statuses: ' . implode(', ', $status_array));
    
    $args = array(
        'limit' => -1,
        'status' => $status_array
    );
    
    $orders = wc_get_orders($args);
    twas_log('Found ' . count($orders) . ' orders to check');
    
    foreach ($orders as $order) {
        $order_date = $order->get_date_created();
        $current_status = $order->get_status();
        $minutes_passed = (time() - strtotime($order_date)) / 60;
        
        twas_log("Checking order #{$order->get_id()} - Current status: {$current_status}, Time passed: {$minutes_passed} minutes");
        
        foreach ($status_rules as $rule) {
            if ($current_status === $rule['from']) {
                $required_minutes = ($rule['unit'] === 'hours') ? 
                                  $rule['time'] * 60 : 
                                  $rule['time'];
                
                twas_log("Found matching rule: {$rule['from']} -> {$rule['to']} after {$required_minutes} minutes");
                
                if ($minutes_passed >= $required_minutes) {
                    $time_text = ($rule['unit'] === 'hours') ? 
                                $rule['time'] . ' hours' : 
                                $rule['time'] . ' minutes';
                    
                    $order->update_status(
                        $rule['to'], 
                        sprintf('Automatically changed from %s to %s after %s', 
                            $rule['from'], 
                            $rule['to'], 
                            $time_text
                        )
                    );
                    
                    twas_log("Updated order #{$order->get_id()} to {$rule['to']}");
                } else {
                    twas_log("Not enough time passed for order #{$order->get_id()}, waiting " . 
                            round($required_minutes - $minutes_passed) . " more minutes");
                }
                break;
            }
        }
    }
    
    twas_log('Finished order status update');
}

// Verify cron is working
add_action('init', 'twas_verify_cron');
function twas_verify_cron() {
    if (!wp_next_scheduled('twas_check_orders')) {
        twas_log('Cron schedule not found, re-registering...');
        wp_schedule_event(time(), 'every_five_minutes', 'twas_check_orders');
    }
}

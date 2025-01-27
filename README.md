# ThaiTop Woo Auto Status

A WordPress plugin for automatically changing WooCommerce order statuses after specified time intervals.

## Description

ThaiTop Woo Auto Status helps you automate your WooCommerce order status changes. Set up rules to automatically change order statuses after a specified time period has passed.

## Features

- Create multiple status change rules
- Set time intervals in minutes or hours
- Monitor orders being tracked for status changes
- Debug tools with logging system
- Manual trigger option for status updates
- Real-time order monitoring dashboard

## Installation

1. Upload the `thaitop-woo-auto-status` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Order Auto Status' in your admin menu to configure the rules

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.0 or higher

## Configuration

1. Navigate to 'Order Auto Status' in your WordPress admin menu
2. Add rules by specifying:
   - Initial status
   - Target status
   - Time interval
   - Time unit (minutes/hours)
3. Save your settings

### Cron Job Setup (Optional but Recommended)

For more reliable operation, set up a system cron job to trigger WordPress cron:

1. **Using cPanel (Every minute):**
   ```bash
   * * * * * wget -q -O /dev/null "https://your-site.com/wp-cron.php?doing_wp_cron"
   ```

2. **Direct Server Access (Every minute):**
   ```bash
   * * * * * php /path/to/wp-cron.php
   ```

Note: 
- Replace "your-site.com" with your actual domain
- Plugin logs will record every check (once per minute)
- For high-traffic sites, consider using */5 instead of * to reduce server load
- Log entries are limited to last 100 entries by default

### Performance Considerations

1. **High-traffic sites:**
   - Use */5 for cron timing
   - Monitor server load
   - Check log file size regularly

2. **Low-traffic sites:**
   - Can safely use * (every minute)
   - Logs will show more frequent checks
   - Consider increasing log retention (modify twas_log function)

## Debug Tools

Access debug tools from the 'Debug Tools' submenu to:
- View the status update log
- Manually trigger status updates
- Clear the debug log
- Monitor scheduled tasks

## Support

For support, please create an issue in the plugin's repository or contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

## Author

ThaiTop
Version: 1.0.0
# Changelog

### 1.0.0 (2024.11.07)
- Initial release.

### 1.0.1 (2025.01.24)
- Added new event `add_shipping_info`
- Added plugin settings page
- Added user-friendly events log
- Added user-friendly error log

### 1.0.2 (2025.01.28)
- Updated event log AJAX handler to unserialize and convert event data to readable JSON.
- Improved front-end JavaScript to dynamically display event logs in a prettified format using `<details>` and `<pre>` blocks.
- Simplified large sub-arrays in event data (e.g., "custom_fields") for cleaner UI presentation.
- Fixed styles for the "Event Logs" table by ensuring compatibility with WordPress `widefat` classes.
- Added better tab navigation and loading indicators for the settings page (README and Changelog sections).
- Optimized pagination logic to improve performance and enhance user experience.
- General code cleanup and minor bug fixes for smoother functionality.
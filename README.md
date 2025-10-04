
## Job Board Plugin

A simple WordPress Job Board plugin that provides a custom post type for job listings, a shortcode to display active listings, and an application submission system with resume upload and admin listing.

This README covers installation, usage, templates, troubleshooting, and developer notes.

## Features
- Custom post type: `job_listing` (Job Listings)
- Front-end shortcode: `[job_board]` to show job listings with search and pagination
- Single job template included at `includes/templates/single-job_listing.php` (plugin template used for single job pages)
- Job application form with resume upload and admin page to view submissions
- PSR-4 autoloading for plugin classes (`JobBoardPlugin\` => `includes/`)

## Installation
1. Copy the plugin folder into your site's plugin directory (`wp-content/plugins/job-board-plugin`).
2. Install Composer autoloader (optional but recommended):

```powershell
# From the plugin directory (Windows PowerShell)
composer dump-autoload -o
```

This generates `vendor/autoload.php` so classes under `includes/` are autoloaded by Composer. If `vendor/autoload.php` is missing, the plugin falls back to including core files directly.

3. Activate the plugin from the WordPress admin Plugins screen.

## Usage
- Add the shortcode to any page to display the job board:

```
[job_board]
```

- Create Job Listings via the WordPress admin (Add New -> Job Listing). Use the Job Details meta box to set Company Name, Address, Application Start, and Application Deadline.

- The job listings template (`includes/templates/job-listings.php`) only shows jobs that are within start/deadline constraints.

## Single Job Template
The plugin includes a default single template at:

```
includes/templates/single-job_listing.php
```

The plugin attempts to make WordPress use this template for single `job_listing` posts by hooking `single_template` and also adding a stronger `template_include` fallback. If you want to override the plugin template in your theme, copy the file into your theme (for example within your theme root) and customize it there.

## Admin: View Applications
The plugin stores job applications in a custom DB table (prefix_job_applications). An admin menu entry "Job Applications" lists received applications and provides resume download links.

## Troubleshooting
- If single job pages do not use the plugin template:
    1. Flush permalinks: go to Settings → Permalinks and click "Save Changes".
    2. Ensure the post type is `job_listing` and posts are published.
    3. Make sure the `Job_Board` class is instantiated (no fatal errors during plugin activation). Check PHP error logs.

- If classes are not autoloading:
    - Run `composer dump-autoload -o` inside the plugin folder to regenerate `vendor/autoload.php`.

- If the job list or permalinks look wrong after changing rewrite slug or CPT settings, flush rewrite rules as above.

## Developer Notes
- PSR-4 mapping (in `composer.json`):

```json
"autoload": {
    "psr-4": { "JobBoardPlugin\\": "includes/" }
}
```

- Namespace for classes: `JobBoardPlugin`. Files under `includes/` should follow PSR-4 file naming. Example mappings:
    - `\JobBoardPlugin\Job_Board` -> `includes/Job_Board.php`
    - `\JobBoardPlugin\Job_CPT` -> `includes/Job_CPT.php`
    - `\JobBoardPlugin\Job_Application` -> `includes/Job_Application.php`

- The plugin includes fallback `require_once` entries in `job-board.php` so it works even if `vendor/autoload.php` is not present.

- Activation hook: the plugin registers an activation handler to create the applications table. If you add or change the CPT rewrite slug, remember to flush rewrite rules.

## Recommended next steps for development
- Add unit/integration tests where possible.
- Consider moving templates to a `templates/` subfolder in the theme when overriding (copy the plugin template into your theme and register via `template_include` filters if you need advanced behavior).

## License
GPL-2.0+ (see `license.txt` in the plugin root if present)



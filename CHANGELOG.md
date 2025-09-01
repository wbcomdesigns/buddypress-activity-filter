# Changelog
All notable changes to BuddyPress Activity Filter will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.2.0] - 2025-09-01

### Fixed
- **Critical**: Disabled activity types were still being created in BuddyPress due to ineffective prevention method
- **UI**: Removed duplicate "Friendship Accepted" option as BuddyPress only creates `friendship_created` activities
- **UI**: Removed `friends_register_activity_action` artifact from activity filter options (not a real activity type)
- **Database**: Fixed malformed serialized data in database options causing filter failures
- **Frontend**: Fixed dropdown resetting on page reload by improving JavaScript timing and synchronization
- **CPT**: Fixed duplicate text in activity actions ("published a new published a new movie" → "published a new movie")

### Changed
- **Performance**: Implemented server-side default filtering for better performance and reliability
- **Architecture**: Default filters now apply at database query level using `bp_after_has_activities_parse_args`
- **Compatibility**: Added support for AJAX filtering with `bp_ajax_querystring` hook
- **JavaScript**: Reduced JavaScript dependency with server-side filtering approach

### Added
- **Prevention**: Proper activity prevention by setting both type and component to empty strings
- **CPT Support**: Conservative eligibility checking to prevent conflicts with UI/template post types
- **Elementor**: Explicit exclusion of Elementor library and floating button post types
- **Developer**: Debug mode support for troubleshooting activity filters
- **Filters**: Proper handling of comma-separated activity types in filters

### Improved
- **Helper Class**: Better activity type filtering and exclusion logic
- **Cookie Handling**: Improved dropdown synchronization with cookie persistence
- **Code Quality**: Cleaned up deprecated JavaScript methods and improved timing

## [3.1.0] - 2024-12-15

### Added
- Redesigned backend UI for better usability
- Vertical layout support for hidden activities with core protection
- Custom wrapper structure for improved layout and organization
- Condition checks for BuddyPress compatibility

### Changed
- Cleaned up and optimized shared folders and unused code
- Updated asset loading for improved performance
- Improved frontend filter styling and selection UI
- Updated frontend wrapper code and applied CSS improvements
- Refined frontend JS to prevent conflicts with admin default filter settings
- Filter enhancements to prevent duplicate or previously registered activities

### Developer
- Introduced `BP_Activity_Filter_Migration` for smoother transitions
- Improved structure through modular wrapper additions and CSS

### Fixed
- Resolved UI inconsistencies with the new wrapper layout
- Removed debug logs and cleaned up dev artifacts

## [3.0.1] - 2024-11-20

### Fixed
- Warning related to page parameter in activity query
- Pagination issue for activity streams where "Load More" button was not functioning correctly

### Improved
- Added check to ensure $page is a string before processing

## [3.0.0] - 2024-10-15

### Fixed
- PHP warning issue
- Issue in filtering activities
- Activity filter applied correctly when viewing "just-me" or "sitewide" activities
- Bypass default activity filter on profile other tabs

### Improved
- Cookie deletion when saving admin options

### Added
- Check to prevent setting default activity filter on single activity views

## [2.9.0] - 2024-09-10

### Changed
- Ensured lowercase post type names when no new label is provided
- Corrected typos and updated readme for clarity
- Removed deprecated filters and modernized PHP code
- Replaced deprecated functions with modern alternatives
- Improved data sanitization and validation

## Earlier Versions
For changelog of earlier versions, please refer to the WordPress.org plugin repository.
# LUDIC course format : Release Notes

## 3.5.2 - 2020-11-05

### ADDED

- New ACHIEVEMENT course module skin type added
- Special student view mode added for single section courses
- Added rocket-story section skin
- Added discrete grade-based score activity skin

### CHANGED

- The editor widget for setting weight values for course module instances is now a free text value
- The PROGRESSION section skin now exposes a 'target' value that can be set on a per-instance basis
- A few tweeks were made to improve page layout in different places

### FIXED

- Fix behavior of course editing toggle, removing student view button that had side effects

## 3.5.1 - 2020-10-07

### ADDED

- Support for Moodle 3.9 - fixing issue with new resource and activity selection popup

### CHANGED

- Size of section in editiong mode => 2x smaller
- Scroll system in editing mode to allow fuller use of whole screen for course content
- Default section type is now PROGRESS not FIXED IMAGE
- Default content type is now ACHIEVEMENET (a new type) not FIXED IMAGE
- Display of skin in form pane in edito mode now includes the skin name and description
- Non-ludic skin types have been re-classified as default user skins instead of system skins making them user-editable
- The default fixed image skins (that were only there to serve as a basic example) have been removed as the non-ludic skins now cover thie purpose
- Clean code

### FIXED

- It was possible to inject HTML via CSS properties in skin definitions
- It is no longer possible to delete default skins in the skins editor

## 3.5.0 - 2020-09-30

Initial version submitted to Moodle.org for publication, Supporting Moodle 3.5 to 3.8

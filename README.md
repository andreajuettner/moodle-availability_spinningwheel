# Spinning Wheel Activity - Moodle Availability Condition

A Moodle 5+ availability condition plugin that restricts access to course activities based on a Spinning Wheel spin. Students must unlock activities by spinning the wheel.

## Requirements

- Moodle 5.0+
- [mod_spinningwheel](https://github.com/andreajuettner/moodle-mod_spinningwheel) plugin installed

## Installation

1. Download or clone into `availability/condition/spinningwheel/`:
   ```bash
   cd /path/to/moodle/availability/condition
   git clone https://github.com/andreajuettner/moodle-availability_spinningwheel.git spinningwheel
   ```
2. Log in as site administrator
3. Navigate to **Site administration > Notifications**
4. Follow the on-screen prompts to complete the installation

## Usage

1. Create a Spinning Wheel activity with entry source **"Course activities"**
2. Edit a target activity → **Voraussetzungen / Restrict access**
3. Click **Add restriction** → Select **Spinning Wheel activity**
4. Choose the Spinning Wheel instance from the dropdown
5. Save — the activity is now locked until the student spins and unlocks it

## Testing

```bash
vendor/bin/phpunit --filter availability_spinningwheel
```

## License

GNU GPL v3 or later. See [COPYING](https://www.gnu.org/licenses/gpl-3.0.html) for details.

## Copyright

2026 Andrea Juettner, andrea.juettner@eledia.de; AI-assisted by Claude (Anthropic).

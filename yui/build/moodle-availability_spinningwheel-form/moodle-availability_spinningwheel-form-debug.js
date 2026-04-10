YUI.add('moodle-availability_spinningwheel-form', function (Y, NAME) {

/**
 * JavaScript for form editing Spinning Wheel availability conditions.
 *
 * @module moodle-availability_spinningwheel-form
 * @copyright 2026 Andrea Juettner, andrea.juettner@eledia.de; AI-assisted by Claude (Anthropic).
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.availability_spinningwheel = M.availability_spinningwheel || {};

/**
 * @class M.availability_spinningwheel.form
 * @extends M.core_availability.plugin
 */
M.availability_spinningwheel.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} wheels Array of objects containing id and name.
 */
M.availability_spinningwheel.form.initInner = function(wheels) {
    this.wheels = wheels;
};

/**
 * Gets the form node for this condition.
 *
 * @method getNode
 * @param {Object} json Current saved value.
 * @return {Object} YUI node for the form.
 */
M.availability_spinningwheel.form.getNode = function(json) {
    var html = '<span class="col-form-label pe-3"> ' +
            M.util.get_string('title', 'availability_spinningwheel') + '</span>' +
            ' <span class="availability-group mb-3"><label>' +
            '<span class="accesshide">' +
            M.util.get_string('label_wheel', 'availability_spinningwheel') +
            ' </span>' +
            '<select class="form-select" name="wheelid" title="' +
            M.util.get_string('label_wheel', 'availability_spinningwheel') + '">' +
            '<option value="0">' + M.util.get_string('choosedots', 'moodle') + '</option>';

    for (var i = 0; i < this.wheels.length; i++) {
        var wheel = this.wheels[i];
        html += '<option value="' + wheel.id + '">' + wheel.name + '</option>';
    }
    html += '</select></label></span>';

    var node = Y.Node.create('<span class="d-flex flex-wrap align-items-center">' + html + '</span>');

    // Set initial value.
    if (json.wheelid !== undefined &&
            node.one('select[name=wheelid] > option[value=' + json.wheelid + ']')) {
        node.one('select[name=wheelid]').set('value', '' + json.wheelid);
    }

    // Add event handler (first time only).
    if (!M.availability_spinningwheel.form.addedEvents) {
        M.availability_spinningwheel.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_spinningwheel select');
    }

    return node;
};

/**
 * Fills the value from the form node.
 *
 * @method fillValue
 * @param {Object} value Object to fill with values.
 * @param {Object} node YUI node for the form.
 */
M.availability_spinningwheel.form.fillValue = function(value, node) {
    value.wheelid = parseInt(node.one('select[name=wheelid]').get('value'), 10);
};

/**
 * Fills errors if the form is not valid.
 *
 * @method fillErrors
 * @param {Array} errors Array to push error strings to.
 * @param {Object} node YUI node for the form.
 */
M.availability_spinningwheel.form.fillErrors = function(errors, node) {
    var wheelid = parseInt(node.one('select[name=wheelid]').get('value'), 10);
    if (wheelid === 0) {
        errors.push('availability_spinningwheel:error_selectwheel');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});

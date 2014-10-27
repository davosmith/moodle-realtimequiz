<?php
/**
 * This implements admin settings in realtimequiz
 *
 * @author: Davosmith
 * @package mod_realtimequiz
 **/

defined('MOODLE_INTERNAL') || die;

/**
 * Admin setting for code source, adds validation.
 *
 * @package    mod_aspirelist
 * @copyright  2014 Lancaster University {@link http://www.lancaster.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class realtimequiz_awaittime_setting extends admin_setting_configtext {

    /**
     * Validate data.
     *
     * @param string $data
     * @return mixed True on success, else error message
     */
    public function validate($data) {
        $result = parent::validate($data);
        if ($result !== true) {
           return $result;
        }
        if ((int)$data < 1) {
           return get_string('awaittimeerror', 'realtimequiz');
        }

        return true;
    }
}
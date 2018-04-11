// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// jshint undef:false, unused:false, scripturl:true

/**
 * Javascript controller for pro services.
 *
 * @module     block_use_stats/pro
 * @package    block_use_stats
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/log', 'core/config', 'core/str'], function($, log, cfg, str) {

    var usestatspro = {

        init: function() {

            $('#id_s_block_use_stats_licensekey').bind('change', this.check_product_key);
            $('#id_s_block_use_stats_licensekey').trigger('change');
            log.debug('AMD Pro js initialized for use_stats');
        },

        check_product_key: function() {

            var that = $(this);

            var productkey = that.val().replace(/-/g, '');
            var payload = productkey.substr(0, 14);
            var crc = productkey.substr(14, 2);

            var calculated = usestatspro.checksum(payload);

            var validicon = ' <img src="' + cfg.wwwroot + '/pix/i/valid.png' + '">';
            var cautionicon = ' <img src="' + cfg.wwwroot + '/pix/i/warning.png' + '">';
            var invalidicon = ' <img src="' + cfg.wwwroot + '/pix/i/invalid.png' + '">';
            var waiticon = ' <img src="' + cfg.wwwroot + '/pix/i/ajaxloader.gif' + '">';

            if (crc === calculated) {
                url = cfg.wwwroot + '/blocks/use_stats/pro/ajax/services.php?';
                url += 'what=license';
                url += '&service=check';
                url += '&customerkey=' + that.val();
                url += '&provider=' + $('#id_s_block_use_stats_licenseprovider').val();

                $('#id_s_block_use_stats_licensekey + img').remove();
                $('#id_s_block_use_stats_licensekey').after(waiticon);

                $.get(url, function(data) {
                    if (data.match(/SET OK/)) {
                        $('#id_s_block_use_stats_licensekey + img').remove();
                        $('#id_s_block_use_stats_licensekey').after(validicon);
                    } else {
                        $('#id_s_block_use_stats_licensekey + img').remove();
                        $('#id_s_block_use_stats_licensekey').after(invalidicon);
                    }
                }, 'html');
            } else {
                $('#id_s_block_use_stats_licensekey + img').remove();
                $('#id_s_block_use_stats_licensekey').after(cautionicon);
            }
        },

        /*
         * Calculates a checksum on 2 chars.
         */
        checksum: function(keypayload) {

            var crcrange = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            var crcrangearr = crcrange.split('');
            var crccount = crcrangearr.length;
            var chars = keypayload.split('');
            var crc = 0;

            for (var ch in chars) {
                var ord = chars[ch].charCodeAt(0);
                crc += ord;
            }

            var crc2 = Math.floor(crc / crccount) % crccount;
            var crc1 = crc % crccount;
            return '' + crcrangearr[crc1] + crcrangearr[crc2];
        }
    };

    return usestatspro;
})
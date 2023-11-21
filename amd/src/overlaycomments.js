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

/**
 * @package    mod_instaplay
 * @copyright  2023 Shruthi S
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function () {

    return {

  sendMesage: function(overlaycomments) {

        const iFrame = document.getElementById('instaplayframeElem');

        const data = JSON.parse(overlaycomments);

        window.console.log(data);

        // here we are creating another object with the overlay data and a
        // additional key 'type', this type checking is needed on the player side
        const eventMsg = {
            type: 'overlay-data',
            msg: data
        };

        window.addEventListener('message', (event) => {
            if (event.data) {
                // here we are checking if the request string matches
                // in future we might have other requests as well so identification needed
                if (event.data === 'get-overlay-data') {
                    // if its a request for overlay we are sending our constructed msg
                    iFrame.contentWindow.postMessage(eventMsg, '*');
                }
            }
        });


    }
};


});
/**
 * MIT License
 *
 * Copyright (c) 2020 DIGITAL RETAIL TECHNOLOGIES SL
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    DIGITAL RETAIL TECHNOLOGIES SL <mail@simlachat.com>
 *  @copyright 2020 DIGITAL RETAIL TECHNOLOGIES SL
 *  @license   https://opensource.org/licenses/MIT  The MIT License
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */
$(function () {
    function RetailcrmAdvancedSettings() {
        this.resetButton = $('input[id="reset-jobs-submit"]').get(0);
        this.form = $(this.resetButton).closest('form').get(0);

        this.resetAction = this.resetAction.bind(this);

        $(this.resetButton).click(this.resetAction);
    }

    RetailcrmAdvancedSettings.prototype.resetAction = function (event) {
        event.preventDefault();

        this.resetButton.disabled = true;
        let data = {
            submitretailcrm: 1,
            ajax: 1,
            RETAILCRM_RESET_JOBS: 1
        };

        let _this = this;

        $.ajax({
            url: this.form.action,
            method: this.form.method,
            timeout: 0,
            data: data,
            dataType: 'json',
        })
            .done(function (response) {
                alert('Reset completed successfully')
                _this.resetButton.disabled = false;
            })
            .fail(function (error) {
                alert('Error: ' + error.responseJSON.errorMsg);
                _this.resetButton.disabled = false;
            })
    }

    window.RetailcrmAdvancedSettings = RetailcrmAdvancedSettings;
});

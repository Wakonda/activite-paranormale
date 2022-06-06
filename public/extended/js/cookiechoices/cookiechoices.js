/*
 Copyright 2014 Google Inc. All rights reserved.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */

(function(window) {

    if (!!window.cookieChoices) {
        return window.cookieChoices;
    }

    var cookieChoices = (function() {

        var cookieName = 'displayCookieConsent';
        var cookieConsentId = 'cookieChoiceInfo';
        var dismissLinkId = 'cookieChoiceDismiss';
        var dismissIconId = 'cookieChoiceDismissIcon';

        function showCookieBar(data) {

            if (typeof data != 'undefined' && typeof data.linkHref != 'undefined') {

                data.position = data.position || "bottom";
                data.language = data.language || "en";
                data.styles = data.styles || 'position:fixed; width:100%; background-color:rgba(238, 238, 238, 0.9); margin:0; left:0; ' + data.position + ':0; padding:4px; z-index:1000; text-align:center;';


                switch (data.language) {

                    case "de":

                        data.cookieText = data.cookieText || "Cookies erleichtern die Bereitstellung unserer Dienste. Mit der Nutzung unserer Dienste erklären Sie sich damit einverstanden, dass wir Cookies verwenden. ";
                        data.dismissText = data.dismissText || "OK";
                        data.linkText = data.linkText || "Weitere Informationen";

                        break;

                    case "it":

                        data.cookieText = data.cookieText || "I cookie ci aiutano ad erogare servizi di qualità. Utilizzando i nostri servizi, l'utente accetta le nostre modalità d'uso dei cookie.";
                        data.dismissText = data.dismissText || "OK";
                        data.linkText = data.linkText || "Ulteriori informazioni";

                        break;

                    case "fr":

                        data.cookieText = data.cookieText || "Les cookies nous permettent de vous proposer nos services plus facilement. En utilisant nos services, vous nous donnez expressément votre accord pour exploiter ces cookies.";
                        data.dismissText = data.dismissText || "OK";
                        data.linkText = data.linkText || "En savoir plus";

                        break;

                    case "nl":

                        data.cookieText = data.cookieText || "Cookies helpen ons bij het leveren van onze diensten. Door gebruik te maken van onze diensten, gaat u akkoord met ons gebruik van cookies.";
                        data.dismissText = data.dismissText || "OK";
                        data.linkText = data.linkText || "Meer informatie";

                        break;

                    case "fi":

                        data.cookieText = data.cookieText || "Evästeet auttavat meitä palvelujemme toimituksessa. Käyttämällä palvelujamme hyväksyt evästeiden käytön.";
                        data.dismissText = data.dismissText || "Selvä";
                        data.linkText = data.linkText || "Lisätietoja";

                        break;

                    default:

                        data.cookieText = data.cookieText || "Cookies help us deliver our services. By using our services, you agree to our use of cookies.";
                        data.dismissText = data.dismissText || "Got it";
                        data.linkText = data.linkText || "Learn more";

                }

                _showCookieConsent(data.cookieText, data.dismissText, data.linkText, data.linkHref, data.styles, false);

            }

        }

        function _createHeaderElement(cookieText, dismissText, linkText, linkHref, styles) {
            var butterBarStyles = styles;
            var cookieConsentElement = document.createElement('div');
            var wrapper = document.createElement('div');
            wrapper.style.cssText = "padding-right: 50px;";

            cookieConsentElement.id = cookieConsentId;
            cookieConsentElement.style.cssText = butterBarStyles;

            wrapper.appendChild(_createConsentText(cookieText));
            if (!!linkText && !!linkHref) {
                wrapper.appendChild(_createInformationLink(linkText, linkHref));
            }
            wrapper.appendChild(_createDismissLink(dismissText));

            cookieConsentElement.appendChild(wrapper);
            cookieConsentElement.appendChild(_createDismissIcon());

            return cookieConsentElement;
        }

        function _createDialogElement(cookieText, dismissText, linkText, linkHref) {
            var glassStyle = 'position:fixed;width:100%;height:100%;z-index:999;' +
                'top:0;left:0;opacity:0.5;filter:alpha(opacity=50);' +
                'background-color:#ccc;';
            var dialogStyle = 'z-index:1000;position:fixed;left:50%;top:50%';
            var contentStyle = 'position:relative;left:-50%;margin-top:-25%;' +
                'background-color:#fff;padding:20px;box-shadow:4px 4px 25px #888;';

            var cookieConsentElement = document.createElement('div');
            cookieConsentElement.id = cookieConsentId;

            var glassPanel = document.createElement('div');
            glassPanel.style.cssText = glassStyle;

            var content = document.createElement('div');
            content.style.cssText = contentStyle;

            var dialog = document.createElement('div');
            dialog.style.cssText = dialogStyle;

            var dismissLink = _createDismissLink(dismissText);
            dismissLink.style.display = 'block';
            dismissLink.style.textAlign = 'right';
            dismissLink.style.marginTop = '8px';

            content.appendChild(_createConsentText(cookieText));
            if (!!linkText && !!linkHref) {
                content.appendChild(_createInformationLink(linkText, linkHref));
            }
            content.appendChild(dismissLink);
            dialog.appendChild(content);
            cookieConsentElement.appendChild(glassPanel);
            cookieConsentElement.appendChild(dialog);
            return cookieConsentElement;
        }

        function _setElementText(element, text) {
            // IE8 does not support textContent, so we should fallback to innerText.
            var supportsTextContent = 'textContent' in document.body;

            if (supportsTextContent) {
                element.textContent = text;
            } else {
                element.innerText = text;
            }
        }

        function _createConsentText(cookieText) {
            var consentText = document.createElement('span');
            _setElementText(consentText, cookieText);
            return consentText;
        }

        function _createDismissLink(dismissText) {
            var dismissLink = document.createElement('a');
            _setElementText(dismissLink, dismissText);
            dismissLink.id = dismissLinkId;
            dismissLink.href = '#';
            dismissLink.style.marginLeft = '24px';
            return dismissLink;
        }

        function _createDismissIcon() {
            var dismissIcon = document.createElement('a');
            dismissIcon.id = dismissIconId;
            dismissIcon.href = '#';
            dismissIcon.style.cssText = 'width: 50px; height: 100%; background-size: 20px; display: inline-block; position: absolute; right: 0px; top: 0px; background-position: 34% 50%; background-color: rgba(204, 204, 204, 0.6); background-repeat: no-repeat; background-image: url(data:image/png;base64,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);';
            return dismissIcon;
        }

        function _createInformationLink(linkText, linkHref) {
            var infoLink = document.createElement('a');
            _setElementText(infoLink, linkText);
            infoLink.href = linkHref;
            infoLink.target = '_blank';
            infoLink.style.marginLeft = '8px';
            return infoLink;
        }

        function _dismissLinkClick() {
            _saveUserPreference();
            _removeCookieConsent();
            return false;
        }

        function _showCookieConsent(cookieText, dismissText, linkText, linkHref, styles, isDialog) {
            if (_shouldDisplayConsent()) {
                _removeCookieConsent();
                var consentElement = (isDialog) ?
                    _createDialogElement(cookieText, dismissText, linkText, linkHref) :
                    _createHeaderElement(cookieText, dismissText, linkText, linkHref, styles);
                var fragment = document.createDocumentFragment();
                fragment.appendChild(consentElement);
                document.body.appendChild(fragment.cloneNode(true));
                document.getElementById(dismissLinkId).onclick = _dismissLinkClick;
                document.getElementById(dismissIconId).onclick = _dismissLinkClick;
            }
        }

        function showCookieConsentBar(cookieText, dismissText, linkText, linkHref) {
            _showCookieConsent(cookieText, dismissText, linkText, linkHref, false);
        }

        function showCookieConsentDialog(cookieText, dismissText, linkText, linkHref) {
            _showCookieConsent(cookieText, dismissText, linkText, linkHref, true);
        }

        function _removeCookieConsent() {
            var cookieChoiceElement = document.getElementById(cookieConsentId);
            if (cookieChoiceElement != null) {
                cookieChoiceElement.parentNode.removeChild(cookieChoiceElement);
            }
        }

        function _saveUserPreference() {
            // Set the cookie expiry to one year after today.
            var expiryDate = new Date();
            expiryDate.setFullYear(expiryDate.getFullYear() + 1);
            document.cookie = cookieName + '=y;path=/; expires=' + expiryDate.toGMTString();
        }

        function _shouldDisplayConsent() {
            // Display the header only if the cookie has not been set.
            return !document.cookie.match(new RegExp(cookieName + '=([^;]+)'));
        }

        var exports = {};
        exports.showCookieBar = showCookieBar;
        exports.showCookieConsentBar = showCookieConsentBar;
        exports.showCookieConsentDialog = showCookieConsentDialog;
        return exports;
    })();

    window.cookieChoices = cookieChoices;
    return cookieChoices;
})(this);

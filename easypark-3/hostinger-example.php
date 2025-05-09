
<!DOCTYPE html>
<html>
<head>
    <!-- Google Tag Manager -->
<script>
    (function (w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
        var f = d.getElementsByTagName(s)[0],
            j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
        j.async = true;
        j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
        f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-KL4FQVG');
</script>
<!-- End Google Tag Manager -->

<script>
    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));

        return match ? match[2] : null;
    }

    function setCookie(name, value, days, domain) {
        const expires = new Date();

        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));

        const cookieValue = encodeURIComponent(value) + '; expires=' + expires.toUTCString() + '; path=/; domain=' + domain + '; SameSite=None; Secure';

        document.cookie = name + '=' + cookieValue;
    }

    function getParentDomain() {
        const hostnameParts = window.location.hostname.split('.');

        return (hostnameParts.length > 2)
            ? '.' + hostnameParts.slice(-2).join('.')
            : '.' + hostnameParts.join('.');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (!getCookie('cookie_consent')) {
            setCookie('cookie_consent', 'statistics,advertising', 730, getParentDomain());
        }
    });
</script>

<script async src="https://www.googletagmanager.com/gtag/js?id=G-73N1QWLEMH"></script>
<script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }

    gtag('js', new Date());
    gtag('config', 'G-73N1QWLEMH');

    function getCookie(name) {
        return document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)')?.pop();
    }

    const consentCookie = getCookie('cookie_consent') || '';
    const isAdvertisingGranted = consentCookie.includes('advertising');
    const isStatisticsGranted = consentCookie.includes('statistics');

    function getConsentPreferences() {
        return {
            'ad_storage': isAdvertisingGranted ? 'granted' : 'denied',
            'ad_user_data': isAdvertisingGranted ? 'granted' : 'denied',
            'ad_personalization': isAdvertisingGranted ? 'granted' : 'denied',
            'analytics_storage': isStatisticsGranted ? 'granted' : 'denied',
            'functionality_storage': 'granted'
        };
    }

    const consentPreferences = getConsentPreferences();

    gtag('consent', 'default', consentPreferences);

    window.dataLayer.push({
        'event': 'consent_init',
        'consent': consentPreferences,
        '_clear': true
    });

    function pushSessionIdToAllForms() {
        gtag(
            "get",
            'G-73N1QWLEMH',
            'session_id',
            (value) => {
                pushHiddenInputToAllForms('ga_session_id', value);
                appendQueryParameterToFilteredLinks('gsession_id', value, 'oauth');
            }
        );
    }

    function pushClientIdToAllForms() {
        gtag(
            "get",
            'G-73N1QWLEMH',
            'client_id',
            (value) => pushHiddenInputToAllForms('ga_client_id', value)
        );
    }

    function pushHiddenInputToAllForms(name, value) {
        const forms = document.querySelectorAll('form');
        const hiddenInput = generateHiddenInput(name, value);

        forms.forEach(form => {
            form.appendChild(hiddenInput);
        });
    }

    function generateHiddenInput(name, value) {
        const hiddenInput = document.createElement('input');

        hiddenInput.type = 'hidden';
        hiddenInput.name = name;
        hiddenInput.value = value;

        return hiddenInput;
    }

    function appendQueryParameterToFilteredLinks(name, value, filter) {
        const links = document.querySelectorAll('a');

        links.forEach(link => {
            const href = link.getAttribute('href');

            if (!href.includes(filter)) {
                return;
            }

            const separator = href.includes('?') ? '&' : '?';

            link.setAttribute('href', `${href}${separator}${name}=${encodeURIComponent(value)}`);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (isStatisticsGranted) {
            pushSessionIdToAllForms();
            pushClientIdToAllForms();
        }
    });
</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log-in to Hostinger</title>
    <link rel="icon" type="image/x-icon" href="https://auth.hostinger.com/assets/images/brand/hostinger/favicon.ico">
    <link href="https://auth.hostinger.com/assets/css/styles.css" rel="stylesheet">
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KL4FQVG" height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
<!-- End Google Tag Manager (noscript) -->
<a class="logo" href="https://hostinger.com" data-qa="homepage-button">
    <img src="https://auth.hostinger.com/assets/images/brand/hostinger/logo.svg" alt="Hostinger">
</a>
<div class="container">
    <div>
                <div class="content">
            <div id="login" class="d-flex justify-content-center align-items-center flex-column position-relative">
                <div class="w-100">
                    <h3 class="mb-25 text-center title">Log in</h3>
                    <span class="w-100">
                    <div class="social-login-block d-flex justify-content-center align-items-center w-100 mb-25">
                                                                            <a href="https://auth.hostinger.com/api/external/v1/oauth/google/login/7d1b2028-d760-403c-8ce7-d7810e807674?redirect_back_url=https://auth.hostinger.com/login" title="Log in with Google" class="p-12-24 b-radius-8 gray-border social" data-qa="google-login-button">
                                <img src="https://auth.hostinger.com/assets/images/oauth/google.svg" alt="Google">
                            </a>
                                                                            <a href="https://auth.hostinger.com/api/external/v1/oauth/facebook/login/7d1b2028-d760-403c-8ce7-d7810e807674?redirect_back_url=https://auth.hostinger.com/login" title="Log in with Facebook" class="p-12-24 mr-16 ml-16 b-radius-8 gray-border social facebook" data-qa="facebook-login-button">
                                <img src="https://auth.hostinger.com/assets/images/oauth/facebook.svg" alt="Facebook">
                            </a>
                                                                            <a href="https://auth.hostinger.com/api/external/v1/oauth/github/login/7d1b2028-d760-403c-8ce7-d7810e807674?redirect_back_url=https://auth.hostinger.com/login" title="Log in with Github" class="p-12-24 b-radius-8 gray-border social github" data-qa="github-login-button">
                                <img src="https://auth.hostinger.com/assets/images/oauth/github.svg" alt="Github">
                            </a>
                                            </div>
                    <div class="or-wrapper mb-25 position-relative">
                        <hr>
                        <p>or</p>
                    </div>
                                    </span>
                    <form method="post" autocomplete="on" class="d-flex justify-content-center align-items-center flex-column w-100">
                        <input type="hidden" name="_token" value="z5r1qyxckAvENXL1YDEpMarc1Dy2jSmy3l7NYwpW" autocomplete="off">                                                <div class="mb-15 w-100">
                            <label for="email-input" class="label">Email address</label>
                            <input id="email-input" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" value="" name="email" type="email" autocomplete="email" class="input color-black" data-qa="email-input" required>
                        </div>
                        <label for="password-input" class="label">Password</label>
                        <div class="mb-8 w-100 text-left position-relative">
                            <input id="password-input" name="password" type="password" class="input color-black" data-qa="password-input" required>
                            <div id="show-password-button" class="position-absolute show-password-eye" data-qa="password-eye-icon">
                                <svg style="fill: #727586" id="eye-icon" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <path clip-rule="evenodd" d="m12 4.5c-5 0-9.27 3.11-11 7.5 1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                                </svg>
                            </div>
                        </div>
                        <a href="/forgot-password" class="w-100 text-left fs-16 mb-25 secondary-action color-hostinger" data-qa="forgot-password-link">Forgot password?</a>

                        <button type="submit" class="mb-25 w-100 primary-action background-color-hostinger" data-qa="login-button">Log in</button>

                                                <a href="/account-recovery" class="mb-25 fs-16 secondary-action color-hostinger" data-qa="account-recovery-link">Can&#039;t Access Your Account?</a>
                    </form>
                    <div class="text-center">
                        Don&#039;t have an account? <a href="register" class="fs-16 secondary-action color-hostinger" data-qa="signup-button">Sign Up</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const emailInputElement = document.getElementById('email-input');
        const passwordInputElement = document.getElementById('password-input');
        const errorDiv = document.getElementById('error-div');
        const showPasswordButton = document.getElementById('show-password-button');
        const eyeIcon = document.getElementById('eye-icon');

        emailInputElement.addEventListener('input', hideErrorDiv);
        passwordInputElement.addEventListener('input', hideErrorDiv);
        showPasswordButton.addEventListener('click', togglePasswordVisibility);

        function togglePasswordVisibility() {
            if (passwordInputElement.type === 'password') {
                passwordInputElement.type = 'text';
                eyeIcon.style.fill = '#673DE6';

                return;
            }

            eyeIcon.style.fill = '#727586';
            passwordInputElement.type = 'password';
        }

        function hideErrorDiv() {
            if (!errorDiv) {
                return;
            }
            errorDiv.style.display = 'none';
        }

        function handleFieldsFocus() {
            if (emailInputElement.value === "") {
                emailInputElement.focus();
                return;
            }
            passwordInputElement.focus();
        }

        handleFieldsFocus();
    });
</script>
<script>
    function getCookie(name) {
        const cookies = document.cookie.split(';');

        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.startsWith(name + '=')) {
                return cookie.substring(name.length + 1);
            }
        }

        return null;
    }

    function getGAValue() {
        
        return getCookie('_ga');
    }

    function getGLValue() {
        
        return null;
    }

    function pushHiddenInputField(name, value) {
        const forms = document.querySelectorAll('form');

        forms.forEach(form => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = name;
            hiddenInput.value = value;

            form.appendChild(hiddenInput);
        });
    }

    function appendQueryParameterToLinks(name, value) {
        const links = document.querySelectorAll('a');

        links.forEach(link => {
            const href = link.getAttribute('href');
            const separator = href.includes('?') ? '&' : '?';

            if (!href.includes(name)) {
                link.setAttribute('href', `${href}${separator}${name}=${encodeURIComponent(value)}`);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const gaValue = getGAValue();
        const glValue = getGLValue();

        if (gaValue) {
            appendQueryParameterToLinks('_ga', gaValue);
            pushHiddenInputField('_ga', gaValue);
        }

        if (glValue) {
            appendQueryParameterToLinks('_gl', glValue);
            pushHiddenInputField('_gl', glValue);
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/@thumbmarkjs/thumbmarkjs/dist/thumbmark.umd.js"></script>
<script>
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    function generateUniqueString() {
        return Date.now().toString(36) + Math.random().toString(36).substring(2);
    }

    function getBrowserLanguage() {
        if (navigator.language) {
            return navigator.language;
        }

        return navigator.userLanguage;
    }

    function getTimeZone() {
        return Intl.DateTimeFormat().resolvedOptions().timeZone;
    }

    document.addEventListener('DOMContentLoaded', () => {
        ThumbmarkJS.getFingerprint().then(fingerprint => {
            if (!(getCookie('thumbmark') !== null)) {
                setCookie('thumbmark', fingerprint, 365);
            }
        });

        if (!(getCookie('thumbmark_uid') !== null)) {
            setCookie('thumbmark_uid', generateUniqueString(), 365);
        }

        setCookie('thumbmark_language', getBrowserLanguage(), 365);
        setCookie('thumbmark_time_zone', getTimeZone(), 365);
    });
</script>
<script>
    function appendQueryParameterToLinks(name, value) {
        const links = document.querySelectorAll('a');

        links.forEach(link => {
            const href = link.getAttribute('href');
            const separator = href.includes('?') ? '&' : '?';

            if (!href.includes(name)) {
                link.setAttribute('href', `${href}${separator}${name}=${encodeURIComponent(value)}`);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
            });
</script>
</body>
</html>

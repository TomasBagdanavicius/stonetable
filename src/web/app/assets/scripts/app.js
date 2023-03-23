'use strict';

/**
 * A shorter reference to the HTML root element
 * @type {HTMLElement}
 */
const htmlElem = document.documentElement;

/**
 * A reference to the "application-name" meta tag element
 * @type {HTMLMetaElement}
 */
const appMeta = document.querySelector('meta[name="application-name"]');

/**
 * Application's name
 * @type {string}
 */
const appName = appMeta.content;

/**
 * Application's version
 * @type {string}
 */
const appVersion = appMeta.dataset.appVersion;

/**
 * Application title's parts
 * @type {array}
 */
const appTitleParts = [appName];

/**
 * Sets up a media query list that when matched would indicate that application
 *    user prefers dark color mode
 * @type {MediaQueryList}
 */
const darkThemeMediaQuery = matchMedia('(prefers-color-scheme:dark)');

/**
 * Stores the saved color mode
 * @type {null|string}
 */
let savedTheme = localStorage.getItem('stonetable_color_mode');

/**
 * Stores the OS's theme name
 * @type {string}
 */
let osTheme = (darkThemeMediaQuery.matches) ? 'dark' : 'light';

/**
 * Sets up a media query list that when matched would indicate compact layout
 * @type {MediaQueryList}
 */
const isCompact = matchMedia('(max-width:660px)');

/**
 * Flags whether the current connection is to a local server
 * @type {null|bool}
 */
let isLocalServer;

/**
 * Application API's latest version endpoint
 * @type {string}
 */
const appApiLatestVersionEndpoint = appMeta.dataset.appApiLatestVersion;

/**
 * Creates a new HTML element
 * @param {string} tagName - HTML elements's tag name
 * @param {object} options - Options: classes, text, id, attrs, title
 * @returns {HTMLElement}
 */
function createElement(tagName, options = {}) {
    const elem = document.createElement(tagName);
    for (const [key, value] of Object.entries(options)) {
        switch (key) {
            case 'classes':
                elem.classList.add(...value);
                break;
            case 'text':
                elem.innerText = value;
                break;
            case 'id':
                elem.id = value;
                break;
            case 'attrs':
                for (const [name, content] of Object.entries(value)) {
                    elem.setAttribute(name, content);
                }
                break;
            case 'title':
                elem.title = value;
                break;
            case 'html':
                elem.innerHTML = value;
                break;
        }
    }
    return elem;
}

/**
 * Normalizes a string by transliterating all non-latin characters to latin
 * @param {string} string - The string to be normalized
 * @returns {string}
 */
function stringTransliterateToLatin(string) {
    return string.normalize('NFD').replace(/\p{Diacritic}/gu, '');
}

/**
 * Inserts a substring into a string at the given index position
 * @param {string} string - The string where the substring will be inserted into
 * @param {number} index - Index position
 * @param {string} substring - The substring to be inserted
 * @returns {string}
 */
function stringInsertAt(string, index, substring) {
    if (isNaN(index)) {
        index = 0;
    }
    return string.slice(0, index).concat(
        substring,
        string.slice(index)
    );
}

/**
 * Searches the entire calling string, and returns all index positions of the
 *     specified substring
 * @param {string} string - The calling string
 * @param {string} searchString - Substring to search for
 * @param {number} index - Starting index position
 * @returns {array} - The index positions of all occurrences of searchString
 *     found, or an empty array if not found.
 */
function stringIndexOfAll(string, searchString, index = 0) {
    let pos;
    const positions = [];
    // Inline with `String.prototype.indexOf`: if position is less than zero,
    // the method behaves as if position were 0.
    index = Math.max(0, index);
    while ((pos = string.indexOf(searchString, index)) !== -1) {
        positions.push(pos);
        index = (pos + 1);
    }
    return positions;
}

/**
 * Adds given before and after strings around substrings at a given position
 *     in a string
 * @param {string} string - String which will be modified
 * @param {array} positions - Start index positions of the substrings
 * @param {number} substringLength - Substring length
 * @param {string} before - Before string that will be inserted at each start
 *     position
 * @param {string} after - After string that will be inserted at each start
 *     position plus substring length
 * @returns {string}
 */
function stringWrap(string, positions, substringLength, before, after) {
    let offset = 0;
    let index;

    positions.forEach(position => {
        index = (position + offset);
        string = stringInsertAt(string, index, before);
        offset += before.length;

        index = (position + offset + substringLength);
        string = stringInsertAt(string, index, after);
        offset += after.length;
    });

    return string;
}

/**
 * Searches for all occurences of a substring in a string and then adds given
 *     before and after strings around found substrings
 * @param {string} string - String where to search for substrings
 * @param {string} substring - String to be searched for
 * @param {string} before - Before string that will be inserted right before
 *     each found substring
 * @param {string} after - After string that will be inserted right after each
 *     foung substring
 * @returns {string}
 */
function markSubstringsInString(
    string,
    substring,
    before = '<mark>',
    after = '</mark>'
) {
    const sourceString = stringTransliterateToLatin(string).toLowerCase();
    const searchString = stringTransliterateToLatin(substring).toLowerCase();
    const positions = stringIndexOfAll(sourceString, searchString);

    if (!positions.length) {
        return string;
    }

    return stringWrap(
        string,
        positions,
        searchString.length,
        before,
        after
    );
}

/**
 * Success callback.
 * @callback onSuccessCallback
 */

/**
 * Failure callback.
 * @callback onFailureCallback
 */

/**
 * Writes given text to clipbboard memory and shows short notifications based on
 *     the result
 * @param {string} text - Text to be written to clipboard memory
 * @param {onSuccessCallback} onSuccess - Callback that will be run on success
 * @param {onFailureCallback} onFailure - Callback that will be run on failure
 */
function writeToClipboard(text, onSuccess, onFailure) {
    navigator.clipboard.writeText(text).then(() => {
        shortNotifications.send(`Copied to clipboard`);
        if (typeof onSuccess === 'function') {
            onSuccess();
        }
    }, reason => {
        raiseDialogMessage(`Failed to write to clipboard: ${reason}`);
        if (typeof onFailure === 'function') {
            onFailure();
        }
    });
}

/**
 * Resets the app title in the document
 */
function resetAppTitle() {
    document.title = appTitleParts.join(' | ');
}

/**
 * Saves given color mode into the local storage
 * @param {string} colorMode - Either "os", "dark", or "light"
 * @returns {boolean}
 */
function saveColorModeInLocalStorage(colorMode) {
    let saved = false;
    try {
        localStorage.setItem('stonetable_color_mode', colorMode);
        savedTheme = colorMode;
        saved = true;
    } catch (error) {
        raiseDialogMessage(
            `Could not save color mode in local storage: ${error.reason}`
        );
    }
    return saved;
}

/**
 * Switches on OS color mode
 */
function switchOnOsColorMode() {
    if (getCurrentTheme() !== osTheme) {
        htmlElem.setAttribute('data-theme', osTheme);
    }
    localStorage.removeItem('stonetable_color_mode');
    savedTheme = undefined;
    htmlElem.setAttribute('data-color-mode', 'os');
}

/**
 * Switches on light color mode
 */
function switchOnLightColorMode() {
    if (
        getCurrentColorMode() !== 'light'
        && saveColorModeInLocalStorage('light')
    ) {
        htmlElem.setAttribute('data-color-mode', 'light');
        htmlElem.setAttribute('data-theme', 'light');
    }
}

/**
 * Switches on dark color mode
 */
function switchOnDarkColorMode() {
    if (
        getCurrentColorMode() !== 'dark'
        && saveColorModeInLocalStorage('dark')
    ) {
        htmlElem.setAttribute('data-color-mode', 'dark');
        htmlElem.setAttribute('data-theme', 'dark');
    }
}

/**
 * Switches on next color mode where the order is "os", "light", "dark"
 */
function switchOnNextColorMode() {
    const currentColorMode = getCurrentColorMode();
    if (currentColorMode === 'os') {
        switchOnLightColorMode();
    } else if (currentColorMode === 'light') {
        switchOnDarkColorMode();
    } else {
        switchOnOsColorMode();
    }
}

/**
 * Switches on given color mode
 * @param {string} colorMode - Either "os", "dark", or "light"
 */
function switchOnChosenColorMode(colorMode) {
    if (colorMode === 'light') {
        switchOnLightColorMode();
    } else if (colorMode === 'dark') {
        switchOnDarkColorMode();
    } else if (colorMode === 'os') {
        switchOnOsColorMode();
    }
}

/**
 * Gets the current active theme
 * @returns {string} - Theme name (dark or light)
 */
function getCurrentTheme() {
    return htmlElem.getAttribute('data-theme');
}

/**
 * Gets the current active color mode
 * @returns {string} - Color mode (os, light, dark)
 */
function getCurrentColorMode() {
    return htmlElem.getAttribute('data-color-mode');
}

/**
 * Reacts to given media query list event changes
 * @param {MediaQueryListEvent} event
 */
function onOsThemeChange(event) {
    // Matches "dark" mode media query.
    if (event.matches) {
        osTheme = 'dark';
    } else {
        osTheme = 'light';
    }

    if (getCurrentColorMode() === 'os') {
        switchOnOsColorMode();
    }
}

if (savedTheme) {
    switchOnChosenColorMode(savedTheme);
} else {
    switchOnOsColorMode();
}

darkThemeMediaQuery.addEventListener('change', onOsThemeChange);

/**
 * Tells if applications is in fullscreen mode
 * @returns {boolean}
 */
function isFullscreen() {
    //todo: refactor when Safari 16.4 is out
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
        return false;
    }
    if (document.fullscreenElement) {
        return document.fullscreenElement === document.body;
    } else {
        return document.webkitFullscreenElement === document.body;
    }
}

/**
 * Puts the application into fullscreen mode
 */
function enterFullscreen() {
    const element = document.body;
    if (element.requestFullscreen) {
        element.requestFullscreen().catch(error => {
            raiseDialogMessage(
                "Error attempting to enable fullscreen mode:"
                + ` ${error.message}`
                + ` (${error.name})`
            );
        });
    //todo: remove when Safari 16.4 is out
    } else if (element.webkitRequestFullscreen) {
        element.webkitRequestFullscreen();
    } else {
        raiseDialogMessage("Fullscreen mode not supported by your browser");
    }
}

/**
 * Moves the application out of fullscreen mode
 */
function exitFullscreen() {
    if (isFullscreen()) {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        //todo: remove when Safari 16.4 is out
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
    }
}

/**
 * Creates a simplified message article widget
 * @param {string} text - Message text
 * @returns {HTMLElement}
 */
function createSimpleMessageElement(text) {
    const article = createElement('article', {
        classes: ['msg', 'msg-shrt'],
    });
    article.append(createElement('p', {
        text,
    }));

    return article;
}

/**
 * Calculates top and left offset positions relative to a given HTML element
 *     and based on the positionable HTML element
 * @param {string} name - Position name (TL, TR, RT, RB, BR, BL, LB, LT)
 * @param {HTMLElement} relElem - HTML Element that is relative to the position
 *     to be calculated
 * @param {HTMLElement} posElem - HTML element that is going to be positioned
 * @returns {array} - Array containing left and top positions
 */
function getTopLeftOffset(name, relElem, posElem) {
    let top, left;
    const relElemRect = relElem.getBoundingClientRect(),
        posElemRect = posElem.getBoundingClientRect(),
        pw = posElemRect.width,
        ph = posElemRect.height,
        vw = htmlElem.clientWidth,
        vh = htmlElem.clientHeight;
    const axisAndPosElemSize = (side) => {
        const axis = (side === 'top' || side === 'bottom') ? 'y' : 'x';
        const p = (axis === 'y') ? ph : pw;
        return [axis, p];
    };
    const calc = (side) => {
        const [axis, p] = axisAndPosElemSize(side);
        const v = (axis === 'y') ? vh : vw;
        return relElemRect[side] + Math.min(0, v - (relElemRect[side] + p));
    };
    const calc2 = (side) => {
        const [, p] = axisAndPosElemSize(side);
        return Math.max(0, relElemRect[side] - p);
    }

    switch(name) {
        case 'TL':
            top = calc2('top');
            left = calc('left');
        break;
        case 'TR':
            top = calc2('top');
            left = calc2('right');
        break;
        case 'RT':
            top = calc('top');
            left = calc('right');
        break;
        case 'RB':
            top = calc2('bottom');
            left = calc('right');
        break;
        case 'BR':
            top = calc('bottom');
            left = calc2('right');
        break;
        case 'BL':
            top = calc('bottom');
            left = calc('left');
        break;
        case 'LB':
            top = calc2('bottom');
            left = calc2('left');
        break;
        case 'LT':
            top = calc('top');
            left = calc2('left');
        break;
    }

    return [top, left];
}

/**
 * Takes the current location URL and checks whether given search parameters and
 *     their corresponding values are in the URL
 * @param {object} params - Parameter key value pairs to check
 * @returns {URL|null} - If nothing was changed in the URL, returns null,
 *     otherwise URL object with amended URL value
 */
function amendLocationUrl(params) {
    const url = new URL(location);
    let changedCount = 0;

    for (const [key, value] of Object.entries(params)) {
        if (
            !url.searchParams.has(key)
            || url.searchParams.get(key) != value
        ) {
            changedCount++;
            if (url.searchParams.has(key)) {
                url.searchParams.delete(key);
            }
            url.searchParams.append(key, value);
        }
    }

    return (!changedCount) ? null : url;
}

/**
 * Removes given search params from the given URL
 * @param {URL} url - URL object which should be used to remove search params
 * @param {array} params - A list of search parameter names
 * @param {boolean} returnIndeces - Whether to return indices
 * @returns {URL|array} - When `returnIndeces` is set to true, it will return an
 *     array containing the URL and removed count number
 */
function removeParamsFromUrl(url, params, returnIndeces = false) {
    let removedCount = 0;

    params.forEach(param => {
        if (url.searchParams.has(param)) {
            url.searchParams.delete(param);
            removedCount++;
        }
    });

    if ( !returnIndeces ) {
        return url;
    } else {
        return [url, removedCount];
    }
}

/**
 * Removes chosen search parameters from the location URL
 * @param {array} params - A list of search parameter names
 */
function removeLocationParams(params) {
    const {url, removedCount} = removeParamsFromUrl(
        new URL(location),
        params,
        true
    );
    if (removedCount) {
        history.replaceState({}, '', url);
    }
}

/**
 * Creates a dialog HTML element with a chosen set of elements that will be
 *     prepended to the dialog
 * @param {iterable} elements - A collection of elements
 * @param {object} options - Metadata for the control buttons
 * @param {array} classes - A list of classes to add to the dialog element
 * @returns {HTMLElement} - The dialog element
 */
function createDialogWith(elements, options, classes = []) {
    const dialogElem = createElement('dialog', {
        classes
    });
    const dialogFormElem = createElement('form', {
        attrs: {
            method: 'dialog',
        }
    });
    const menuElem = createElement('menu');

    // No options given.
    if (!options) {
        menuElem.append(createElement('button', {
            text: "OK",
            attrs: {
                autofocus: '',
            }
        }));
    // Custom options given.
    } else {
        for (const [key, data] of Object.entries(options)) {
            const attrs = {
                'value': key
            };
            if (Object.hasOwn(data, 'autofocus') && data.autofocus === true) {
                attrs.autofocus = '';
            }
            const button = createElement('button', {
                text: data.text,
                attrs,
            });
            if (Object.hasOwn(data, 'onClick')) {
                button.addEventListener('click', e => {
                    data.onClick(e);
                });
            }
            menuElem.append(button);
        }
    }

    dialogFormElem.append(menuElem);
    dialogElem.append(...elements, dialogFormElem);

    return document.body.appendChild(dialogElem);
}

/**
 * Raises a dialog as a modal element with the given elements
 * @param {iterable} elements - A collection of elements to prepend to the
 *     dialog
 * @param {object} options - Metadata for the control buttons
 * @param {array} classes - A list of classes to add to the dialog element
 * @param {closure} onClose - A function to run once the dialog is closed.
 *     Dialog's return value will be passed into this function.
 */
function raiseDialogWith(elements, options, classes, onClose) {
    const dialog = createDialogWith(elements, options, classes);
    dialog.showModal();
    dialog.addEventListener('close', () => {
        if (typeof onClose === 'function') {
            onClose(dialog.returnValue);
        }
        dialog.remove();
    });
}

/**
 * Raises a dialog as a modal with the given text message
 * @param {string} text - Text message
 * @param {object} options - Metadata for the control buttons
 * @param {array} classes - A list of classes to add to the dialog element
 * @param {closure} onClose - A function to run once the dialog is closed.
 *     Dialog's return value will be passed into this function.
 */
function raiseDialogMessage(text, options, classes, onClose) {
    const paragraph = createElement('p', {
        text,
    });
    raiseDialogWith([paragraph], options, classes, onClose);
}

/**
 * Gets the latest app version by sending a request to the app's API endpoint
 * @param {AbortSignal} signal - Arbitrary abort signal for the request
 * @param {number} timeout - Request timeout in miliseconds
 * @returns {Promise} - A promise that fulfills with the version number
 */
async function getLatestAppVersion(signal, timeout = 3000) {
    if (!signal) {
        signal = AbortSignal.timeout(timeout);
    }
    return await fetch(appApiLatestVersionEndpoint, {
        signal
    }).then(response => response.json()).then(data => {
        return data.version;
    }).catch(error => {
        console.error("Could not get latest app version", error);
        throw error;
    });
}

/**
 * Custom error type for the API request error handling
 * @extends Error
 */
class ApiRequestError extends Error {
    constructor(message, options) {
        super(message, options);
        this.name = 'ApiRequestError';
    }
}

/**
 * Custom error type for the request timeout error
 * @extends Error
 */
class RequestTimeoutError extends Error {
    constructor(message, options) {
        super(message, options);
        this.name = 'RequestTimeoutError';
    }
}

/**
 * Custom error type that indicates a screen close exception
 * @extends Error
 */
class ScreenCloseException extends DOMException {
    constructor(message, name) {
        super(message, name);
    }
}

/**
 * Handles a HTTP request to the project's API
 * @param {string|URL} url - Request URL representing an API endpoint
 * @param {AbortController} abortController - Custom abort controller
 * @param {number} timeout - Request timeout in miliseconds
 * @returns {Promise} - A promise that fulfills with the payload data
 */
async function apiRequest(url, abortController, timeout = 5000) {
    if (!abortController) {
        abortController = new AbortController();
    }
    const signal = abortController.signal;
    const headers = new Headers({
        'cache-control': 'private',
    });
    const fetchOptions = {
        signal,
        headers,
    }
    const timeoutHandlerId = setTimeout(() => {
        abortController.abort(new RequestTimeoutError(
            `Request timeout error`
        ));
    }, timeout);
    console.log(`%cAPI request: ${url}`, 'color:royalblue');
    return await fetch(url, fetchOptions).then(response => {
        clearTimeout(timeoutHandlerId);
        if (response.status == 404) {
            throw new ApiRequestError(`Given location was not found`);
        } else if (response.status != 200) {
            throw new ApiRequestError(`Invalid status ${response.status}`);
        } else {
            const contentTypeHeader = response.headers.get('content-type');
            if (!contentTypeHeader) {
                throw new ApiRequestError(
                    `Response is missing content type declaration`
                );
            } else if (
                !contentTypeHeader.startsWith('application/json')
            ) {
                throw new ApiRequestError(`Response is not a JSON application`);
            }
            return response.json();
        }
    }).then(payload => {
        if (!('status' in payload)) {
            throw new ApiRequestError(`Invalid response payload`);
        }
        const {status: status} = payload;
        if (status != 0 && status != 1) {
            throw new ApiRequestError(`Invalid response status`);
        }
        if (status == 0) {
            const message = payload.message || `There has been an error`;
            throw new ApiRequestError(message);
        }
        if (!('data' in payload)) {
            throw new ApiRequestError(`Response is missing the data component`);
        }
        return payload;
    }).catch(error => {
        // Recognizable screen close action.
        if (error instanceof ScreenCloseException) {
            return false;
        // Abort action.
        } else if (
            (
                error.name === 'AbortError'
                || error instanceof RequestTimeoutError
            ) && signal.aborted
        ) {
            return false;
        } else if (
            error instanceof TypeError
            || error instanceof ApiRequestError
        ) {
            raiseDialogMessage(error.message);
            return false;
        } else {
            throw error;
        }
    }).finally(() => {
        // Garbage collect abort controller.
        abortController.abort();
    });
}

/**
 * Creates an "Empty" controller button for a given input field element, binds
 *     it to that input field, and places it after the input field.
 * @param {HTMLInputElement} inputField - Input element
 */
function produceEmptyControllerButton(inputField) {
    let button = null;
    const create = () => {
        button = createElement('button', {
            text: "Empty",
            classes: ['mty-ctrl'],
        });
    }
    const remove = () => {
        if (button) {
            button.remove();
            button = null;
        }
    }
    const place = () => {
        if (!button) {
            create();
            button.addEventListener('click', () => {
                inputField.value = '';
                remove();
                inputField.dispatchEvent(new Event('input', {
                    bubbles: true,
                    cancelable: false
                }));
            });
            inputField.insertAdjacentElement('afterend', button);
        }
    }
    inputField.addEventListener('input', () => {
        if (inputField.value !== '') {
            place();
        } else if (button) {
            remove();
        }
    });
    if (inputField.value !== '') {
        place();
    }
}

/**
 * Binds input element to the closest field element by toggling the "blank" and
 *    "blank-within" classes.
 * @param {HTMLInputElement} input
 */
function bindInputToField(input) {
    const field = input.closest('.fld');
    const checkValue = () => {
        if (input.value === '') {
            input.classList.add('blk');
            field.classList.add('blk-within');
        } else {
            input.classList.remove('blk');
            field.classList.remove('blk-within');
        }
    }
    input.addEventListener('input', checkValue);
    checkValue();
}

/** Base abstract class for the listing object */
class BaseListing {
    wrapper;
    group;
    header;
    footer;
    buildWrapper(classes) {
        return createElement('div', {
            classes: ['lst', ...classes]
        });
    }
    buildHeader() {
        return createElement('div', {
            classes: ['lst-hdr']
        });
    }
    buildGroup(tagName = 'div') {
        return createElement(tagName, {
            classes: ['lst-grp'],
        });
    }
    buildFooter() {
        return createElement('div', {
            classes: ['lst-ftr']
        });
    }
}

/**
 *
 * @param {HTMLDivElement} listingElem - HTML element representing the listing
 * @param {string|URL} endpointUrl - Endpoint URL for the search request
 * @param {callable} entryBuilder - A function that will be called as a filter
 *     when a new group item is created
 * @param {callable} abortControllerProvider - A function that will be used to
 *     issue a new abort controller
 * @param {object} payload - Full response payload (optional)
 * @param {number} pageNumber - Initial page number
 * @param {callable} onListingGroupAppend - A function that will be called each
 *    time something is appending to the listing group
 * @param {callable} onSearchInput - A function that will be called each time
 *     something is entered into the search input field
 */
function SearchableApiListing(
    listingElem,
    endpointUrl,
    entryBuilder,
    abortControllerProvider,
    payload,
    pageNumber = 1,
    onListingGroupAppend,
    onSearchInput
) {
    this.container = listingElem;
    this.searchInput = listingElem.querySelector('[type=search]');
    this.listingGroup = listingElem.querySelector('.lst-grp');
    this.listingBody = listingElem.querySelector('.lst-bd');
    this.loadMoreButton = listingElem.querySelector('.load-more-btn');
    this.pageNumber = pageNumber;
    this.entryBuilder = entryBuilder;
    this.abortControllerProvider = abortControllerProvider;
    this.onListingGroupAppend = onListingGroupAppend;
    this.onSearchInput = onSearchInput;
    this.setEndpointUrl(endpointUrl);
    bindInputToField(this.searchInput);
    produceEmptyControllerButton(this.searchInput);
    this.bindSearchInput();
    const locationURL = new URL(location);
    let isAutoSearch = false;
    if (locationURL.searchParams.has(this.searchInput.name)) {
        this.searchInput.value = locationURL.searchParams.get(
            this.searchInput.name
        );
        this.searchInput.dispatchEvent(new Event('input', {
            bubbles: true,
            cancelable: false
        }));
        isAutoSearch = true;
    }
    this.resizeObserver = this.getResizeObserver();
    if (!isAutoSearch) {
        if (!payload) {
            this.fillUpContents();
        } else {
            this.appendGroupContents(payload);
            this.populateStateData(payload);
            if (this.hasMorePages()) {
                this.fillUpContents().then(() => {
                    this.startObservingListingBody();
                });
            }
        }
    }
    this.loadMoreButton.addEventListener('click', () => {
        this.nextPageRequest().then(payload => {
            if (payload !== false) {
                this.appendGroupContents(payload);
                this.populateStateData(payload);
            }
        });
    });
    this.listingBody.addEventListener('scroll', () => {
        if (this.hasMorePages() && this.hasReachedScrollThreshold()) {
            this.nextPageRequest().then(payload => {
                if (payload) {
                    this.appendGroupContents(payload);
                    this.populateStateData(payload);
                }
            });
        }
    });
}

Object.assign(SearchableApiListing.prototype, {
    setEndpointUrl(endpointUrl) {
        this.endpointUrl = (!(endpointUrl instanceof URL))
            ? new URL(endpointUrl)
            : endpointUrl;
    },
    async fillUpContents() {
        if (this.getRemainingSpace()) {
            return this.nextPageRequestUntil(payload => {
                this.populateStateData(payload);
                this.appendGroupContents(payload);
                return this.hasMorePages() && this.getRemainingSpace();
            });
        } else {
            return null;
        }
    },
    getResizeObserver() {
        let waiting = false;
        const resizeObserver = new ResizeObserver(entries => {
            for (const entry of entries) {
                console.log('resize');
                if (
                    this.getRemainingSpace()
                    // Resize is called when element is removed.
                    && entry.target.isConnected
                ) {
                    console.log("resize has space");
                    if (!waiting && this.hasMorePages()) {
                        console.log("resize go");
                        waiting = true;
                        this.fillUpContents().then(() => {
                            waiting = false;
                        });
                    }
                }
            }
        });
        return resizeObserver;
    },
    startObservingListingBody() {
        this.resizeObserver.observe(this.listingBody, {
            box: 'content-box',
        });
    },
    getRemainingSpace() {
        return Math.max(
            0,
            (this.listingBody.offsetHeight - this.listingGroup.offsetHeight)
        );
    },
    hasReachedScrollThreshold() {
        const scrollHeight = (this.listingBody.offsetHeight
            + this.listingBody.scrollTop);
        return (scrollHeight >= this.listingGroup.offsetHeight);
    },
    populateStateData(payload) {
        this.container.classList.remove('no-rslt');
        this.pageNumber = payload.pageNumber;
        this.maxPages = payload.maxPages;
        // Last page reached.
        if (payload.maxPages <= payload.pageNumber) {
            this.loadMoreButton.disabled = 'true';
        } else {
            this.loadMoreButton.removeAttribute('disabled');
        }
        if (!payload.totalCount) {
            this.container.classList.add('no-data');
            if (this.searchInput.value === '') {
                this.container.classList.add('no-rslt');
            }
        }
    },
    hasMorePages() {
        return (this.maxPages > this.pageNumber);
    },
    searchRequest(searchQuery) {
        const url = this.endpointUrl;
        url.searchParams.set(this.searchInput.name, searchQuery);
        this.request(url.toString());
    },
    getRequestUrl(customParams) {
        const url = this.endpointUrl;
        let searchTerm;
        if (
            customParams
            && Object.hasOwn(customParams, 'searchTerm')
        ) {
            searchTerm = customParams.searchTerm;
        } else if (this.searchInput.value !== '') {
            searchTerm = this.searchInput.value;
        }
        if (searchTerm) {
            url.searchParams.set(this.searchInput.name, searchTerm);
        } else {
            url.searchParams.delete(this.searchInput.name);
        }
        let pageNumber;
        if (
            customParams
            && Object.hasOwn(customParams, 'pageNumber')
        ) {
            pageNumber = customParams.pageNumber;
        } else if (this.pageNumber !== 1) {
            pageNumber = this.pageNumber;
        }
        if (pageNumber) {
            url.searchParams.set('page_number', pageNumber);
        } else {
            url.searchParams.delete('page_number');
        }
        return url;
    },
    async request(customParams) {
        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = (this.abortControllerProvider)
            ? this.abortControllerProvider()
            : new AbortController();
        const url = this.getRequestUrl(customParams);
        return await apiRequest(url, this.abortController).then(payload => {
            return payload;
        }).catch(error => {
            console.error(error);
        }).finally(() => {
            this.container.classList.remove('no-data');
        });
    },
    async nextPageRequest() {
        if (this.pageNumber === this.maxPages) {
            return null;
        }
        return await this.request({
            pageNumber: (this.pageNumber + 1)
        });
    },
    async nextPageRequestUntil(untilHandler) {
        return this.request({
            pageNumber: (this.pageNumber + 1)
        }).then(async payload => {
            if (payload !== false && untilHandler(payload)) {
                await this.nextPageRequestUntil(untilHandler);
            }
        });
    },
    bindSearchInput() {
        this.searchInput.addEventListener('input', () => {
            console.log('search input:', this.searchInput.value);
            if (typeof this.onSearchInput === 'function') {
                this.onSearchInput(this.searchInput);
            }
            this.request({
                pageNumber: 1,
            }).then(payload => {
                if (payload !== false) {
                    const data = payload.data;
                    if (data.length) {
                        this.populateStateData(payload);
                        this.replaceGroupContents(payload);
                        this.fillUpContents();
                    // No results.
                    } else {
                        this.flushGroupContents();
                        this.container.classList.add('no-data');
                        this.loadMoreButton.disabled = 'true';
                    }
                }
            });
        });
    },
    flushGroupContents() {
        this.listingGroup.innerHTML = '';
    },
    replaceGroupContents(payload) {
        this.flushGroupContents();
        this.appendGroupContents(payload);
    },
    appendGroupContents(payload) {
        if (typeof this.onListingGroupAppend === 'function') {
            this.onListingGroupAppend.call(this, payload);
        }
        payload.data.forEach(entry => {
            let item = createElement('div');
            item = this.entryBuilder.call(
                this, entry, item
            );
            if (this.searchInput.value !== '') {
                item.firstElementChild.innerHTML = markSubstringsInString(
                    item.firstElementChild.innerText,
                    this.searchInput.value
                );
            }
            this.listingGroup.append(item);
        });
    },
    resetData(endpointUrl) {
        this.searchInput.value = '';
        this.searchInput.dispatchEvent(new Event('input', {
            bubbles: true,
            cancelable: false
        }));
        this.setEndpointUrl(endpointUrl);
        this.flushGroupContents();
        this.pageNumber = 0;
        return this.fillUpContents();
    }
});

class UnitTestsListing extends BaseListing {

    data;
    meta;
    screen;
    state = 'pending';
    catsTotal = 0;
    catsSelectedCount = 0;
    casesSelectedCount = 0;
    abortControllers = new Set();
    onCountChange = new Set();
    onExpandCountChange = new Set();
    onStateChange = new Set();
    expectedResult = "bool(true)\n";
    expandCount = 0;

    constructor(data, meta, screen, caseRequestTimeout = 3000) {
        super();
        this.data = data;
        this.meta = meta;
        this.screen = screen;
        this.caseRequestTimeout = caseRequestTimeout;
        /* Build group before building the header or the wrapper, because they
        are dependant upon the header. */
        this.group = this.buildGroup();
        this.header = this.buildHeader();
        this.wrapper = this.buildWrapper(['lst-test-case']);
        this.wrapper.dataset.state = this.state;
        this.wrapper.append(this.header, this.group);
        const onClose = () => {
            if (this.state == 'running') {
                this.abortAll();
            }
        };
        this.screen.onClose(onClose);
        if (this.screen.name == 'manager') {
            this.screen.onMainPanelClose(onClose);
        }
    }
    buildHeader() {
        const header = super.buildHeader();
        const dataList = createElement('dl', {
            classes: ['stats']
        });
        [
            ["Categories", this.catsTotal, 'cats-cn'],
            ["Total", this.meta.total, 'tot'],
        ].forEach(([key, value, className]) => {
            dataList.append(createElement('dt', {
                text: key,
                classes: [className]
            }), createElement('dd', {
                text: value
            }))
        });
        const headerInner = createElement('div', {
            classes: ['hdr-inr']
        });
        headerInner.append(this.buildMenu(), dataList);
        header.append(headerInner);
        return header;
    }
    buildCaseList(data) {
        const list = createElement('ol', {
            classes: ['file-l']
        });
        let count = 0;
        for (const [fileId, fileData] of Object.entries(data)) {
            const listItem = createElement('li', {
                attrs: {
                    'data-case-id': fileId,
                }
            });
            const contentElem = createElement('div', {
                classes: ['cnt']
            });
            contentElem.addEventListener('click', () => {
                contentElem.classList.add('act');
            });
            const nameElem = createElement('div', {
                text: fileData.basename,
                title: fileData.basename,
                classes: ['file-ttl']
            });
            const optionsButton = this.screen.createOptionsButtonFor(() => {
                return this.screen.buildContextMenu(fileData);
            });
            contentElem.append(nameElem, optionsButton);
            listItem.append(contentElem);
            list.append(listItem);
            count++;
        }
        return [list, count];
    }
    buildGroup() {
        const group = super.buildGroup('ul');
        for (const [catId, catData] of Object.entries(this.data)) {
            const [fileList, casesCount] = this.buildCaseList(catData.files);
            const listItem = createElement('li', {
                attrs: {
                    'data-category-id': catId,
                    'data-cases-count': casesCount
                }
            });
            const itemContent = createElement('div', {
                classes: ['cnt']
            });
            const stateElem = createElement('div', {
                classes: ['state'],
                attrs: {
                    role: 'button',
                    tabindex: '0',
                },
                title: "Select all in category"
            });
            stateElem.addEventListener('click', e => {
                // Don't propagate to the content element.
                e.stopPropagation();
                if (this.state == 'pending') {
                    if (!listItem.classList.contains('sel')) {
                        listItem.classList.add('sel');
                        this.updateStateCount(
                            this.catsSelectedCount + 1,
                            this.casesSelectedCount + casesCount
                        );
                    } else {
                        listItem.classList.remove('sel');
                        this.updateStateCount(
                            this.catsSelectedCount - 1,
                            this.casesSelectedCount - casesCount
                        );
                    }
                }
            });
            const nameElem = createElement('div', {
                title: catData.name,
                classes: ['cat-ttl']
            });
            const forwardSlashPos = stringIndexOfAll(catData.name, '/');
            let nameHTML = '';
            let baseName;
            if (forwardSlashPos.length) {
                const lastPos = forwardSlashPos[forwardSlashPos.length - 1];
                nameHTML = catData.name.substring(0, lastPos);
                baseName = catData.name.substring(lastPos);
            } else {
                baseName = catData.name;
            }
            nameHTML += `<span class="base">${baseName}</span>`;
            nameElem.innerHTML = `${nameHTML} <span class="file-cn">`
                + `(${casesCount})</span>`;
            itemContent.addEventListener('click', () => {
                const toggle = listItem.classList.toggle('open');
                this.updateExpandCount(this.expandCount + ((toggle) ? 1 : -1));
            });
            itemContent.append(stateElem, nameElem);
            listItem.append(itemContent, fileList);
            group.append(listItem);
            this.catsTotal++;
        }
        return group;
    }
    updateStateCount(newCatsSelectedCount, newCasesSelectedCount) {
        if (typeof newCatsSelectedCount !== 'undefined') {
            this.catsSelectedCount = newCatsSelectedCount;
        }
        if (typeof newCasesSelectedCount !== 'undefined') {
            this.casesSelectedCount = newCasesSelectedCount;
        }
        this.onCountChange.forEach(closure => {
            closure();
        });
    }
    updateExpandCount(newCount) {
        this.expandCount = newCount;
        this.onExpandCountChange.forEach(closure => {
            closure();
        });
    }
    buildMenu() {
        const buttonParams = [{
            text: "Select All",
            classes: ['slt-all'],
            disabled: false,
            onSelectCountChange(button) {
                button.disabled = this.catsSelectedCount === this.catsTotal;
            },
            onClick() {
                const queryStr = ':scope > .lst-grp > :not(.sel)';
                this.wrapper.querySelectorAll(queryStr).forEach(item => {
                    item.classList.add('sel');
                })
                this.updateStateCount(this.catsTotal, this.meta.total);
            },
            onStateChange(button) {
                button.disabled = this.state !== 'pending';
            }
        }, {
            text: "Deselect All",
            classes: ['dslt-all'],
            disabled: true,
            onSelectCountChange(button) {
                button.disabled = !this.catsSelectedCount;
            },
            onClick() {
                const queryStr = ':scope > .lst-grp > .sel';
                this.wrapper.querySelectorAll(queryStr).forEach(item => {
                    item.classList.remove('sel');
                });
                this.updateStateCount(0, 0);
            },
            onStateChange(button) {
                button.disabled = this.state !== 'pending';
            }
        }, {
            text: "Expand All",
            classes: ['exp-all'],
            disabled: false,
            onClick() {
                let queryStr = ':scope > :not(.open)';
                this.group.querySelectorAll(queryStr).forEach(elem => {
                    elem.classList.add('open');
                });
                this.updateExpandCount(this.catsTotal);
            },
            onExpandCountChange(button) {
                button.disabled = this.expandCount === this.catsTotal;
            }
        }, {
            text: "Collapse All",
            classes: ['coll-all'],
            disabled: true,
            onClick() {
                this.group.querySelectorAll(':scope > .open').forEach(elem => {
                    elem.classList.remove('open');
                });
                this.updateExpandCount(0);
            },
            onExpandCountChange(button) {
                button.disabled = !this.expandCount;
            }
        }, {
            text: "Run Selected",
            classes: ['run-slt'],
            disabled: true,
            onSelectCountChange(button) {
                if (!this.catsSelectedCount) {
                    button.disabled = true;
                    button.innerText = "Run Selected";
                } else {
                    button.disabled = false;
                    button.innerText = "Run Selected"
                        + ` (${this.casesSelectedCount})`;
                }
            },
            onClick() {
                this.runAllTests();
            },
            onStateChange(button) {
                button.disabled = this.state !== 'pending';
            }
        }, {
            text: "Rerun",
            disabled: true,
            onClick() {
                // "false" to not reset selected items.
                this.resetAllResults(false);
                this.runAllTests();
            },
            onStateChange(button) {
                button.disabled = this.state !== 'complete';
            }
        }, {
            text: "Reset Results",
            classes: ['rst-rslt'],
            disabled: true,
            onStateChange(button) {
                button.disabled = (this.state != 'complete');
            },
            onClick() {
                this.resetAllResults();
            }
        }, {
            text: "Abort",
            classes: ['abr'],
            disabled: true,
            onStateChange(button) {
                button.disabled = (this.state != 'running');
            },
            onClick() {
                this.abortAll();
            }
        }];
        const menu = createElement('menu');
        for (const params of buttonParams) {
            const button = createElement('button', params);
            if (params.disabled) {
                button.disabled = true;
            }
            if (Object.hasOwn(params, 'onSelectCountChange')) {
                this.onCountChange.add(
                    params.onSelectCountChange.bind(this, button)
                );
            }
            if (Object.hasOwn(params, 'onClick')) {
                button.addEventListener(
                    'click',
                    params.onClick.bind(this, button)
                );
            }
            if (Object.hasOwn(params, 'onStateChange')) {
                this.onStateChange.add(
                    params.onStateChange.bind(this, button)
                )
            }
            if (Object.hasOwn(params, 'onExpandCountChange')) {
                this.onExpandCountChange.add(
                    params.onExpandCountChange.bind(this, button)
                )
            }
            menu.append(button);
        }
        return menu;
    }
    abortAll() {
        if (this.abortControllers.size) {
            this.abortControllers.forEach(abortController => {
                if (!abortController.signal.aborted) {
                    abortController.abort();
                }
            });
        }
        this.changeState('aborted');
    }
    caseItemToFetchPromise(item) {
        const caseId = item.dataset.caseId;
        const categoryItem = item.parentElement.parentElement;
        const catId = categoryItem.dataset.categoryId;
        let url = this.data[catId]['files'][caseId]['url'];
        url = new URL(url);
        url.searchParams.set('format', 'json');
        const headers = new Headers({
            'cache-control': 'private'
        });
        const abortController = this.screen.provideAbortController(
            this.caseRequestTimeout
        );
        return [
            fetch(url, {
                signal: abortController.signal,
                headers
            }),
            abortController,
            catId
        ];
    }
    getCategoryAndCaseItemsById(catId, caseId) {
        let queryStr = `[data-category-id="${catId}"]`;
        const categoryItem = this.wrapper.querySelector(queryStr);
        queryStr = `[data-case-id="${caseId}"]`;
        const caseItem = categoryItem.querySelector(queryStr);
        return [categoryItem, caseItem];
    }
    addProcessedCount(categoryItem) {
        if (!categoryItem.dataset.processedCount) {
            categoryItem.dataset.processedCount = 1;
        } else {
            categoryItem.dataset.processedCount
                = parseInt(categoryItem.dataset.processedCount) + 1;
        }
    }
    markError(catId, caseId, reason, errorMessages) {
        const [categoryItem, caseItem] = this.getCategoryAndCaseItemsById(
            catId, caseId
        );
        categoryItem.dataset.state = 'failed';
        caseItem.dataset.state = 'failed';
        const reasonPara = createElement('p', {
            classes: ['err']
        });
        reasonPara.append(createElement('em', {
            text: reason
        }));
        reasonPara.addEventListener('click', () => {
            reasonPara.classList.add('act');
        });
        if (errorMessages) {
            const errorOptions = this.screen.createOptionsButton();
            errorOptions.addEventListener('click', () => {
                if (!errorOptions.classList.contains('open')) {
                    caseItem.insertAdjacentHTML('beforeend', errorMessages);
                } else {
                    let queryStr = ':scope > .code-msg';
                    caseItem.querySelectorAll(queryStr).forEach(elem => {
                        elem.remove();
                    });
                }
                errorOptions.classList.toggle('open');
            });
            reasonPara.append(errorOptions);
        }
        caseItem.append(reasonPara);
        this.addProcessedCount(categoryItem);
        if (!categoryItem.dataset.failedCount) {
            categoryItem.dataset.failedCount = 1;
        } else {
            categoryItem.dataset.failedCount++;
        }
        const categoryContent = categoryItem.querySelector(':scope > .cnt');
        const failCount = categoryContent.querySelector(':scope > .fail-cn');
        if (!failCount) {
            categoryContent.append(createElement('em', {
                text: "Failed: 1",
                classes: ['fail-cn']
            }));
        } else {
            failCount.innerText
                = `Failed: ${categoryItem.dataset.failedCount}`;
        }
    }
    markPass(catId, caseId) {
        const [categoryItem, caseItem] = this.getCategoryAndCaseItemsById(
            catId, caseId
        );
        caseItem.dataset.state = 'passed';
        this.addProcessedCount(categoryItem);
        const dataset = categoryItem.dataset;
        if (
            dataset.processedCount == dataset.casesCount
            && dataset.state != 'failed'
        ) {
            dataset.state = 'passed';
        }
    }
    changeState(state) {
        this.state = state;
        this.onStateChange.forEach(closure => {
            closure();
        });
        if (this.wrapper) {
            this.wrapper.dataset.state = state;
        }
    }
    runAllTests(chunkSize = 10) {
        this.changeState('running');
        let queryStr = ':scope > .lst-grp > * > .cnt > .state';
        this.wrapper.querySelectorAll(queryStr).forEach(stateElem => {
            stateElem.removeAttribute('role');
            stateElem.removeAttribute('tabindex');
        });
        queryStr = ':scope > .lst-grp > .sel > ol > li';
        const selectedCases = this.wrapper.querySelectorAll(queryStr);
        const stats = {
            passed: {
                className: 'pass',
                count: 0
            },
            failed: {
                className: 'fail',
                count: 0
            },
            processed: {
                className: 'procd',
                count: 0
            }
        };
        let chunkOffset = 0;
        const statsElem = this.header.querySelector('.stats');
        statsElem.insertAdjacentHTML(
            'afterbegin',
            '<dt class="run procd" data-count="0">Processed</dt><dd>0</dd>'
            + '<dt class="run pass" data-count="0">Passed</dt><dd>0</dd>'
            + '<dt class="run fail" data-count="0">Failed</dt><dd>0</dd>'
        );
        const updateMetaElem = (className, count, outOf) => {
            const outOfStr = (outOf)
                ? `/${outOf}`
                : '';
            let queryStr = `:scope > .${className}`;
            const targetElem = statsElem.querySelector(queryStr);
            targetElem.dataset.count = count;
            targetElem.nextElementSibling.textContent = `${count}${outOfStr}`;
        }
        const process = (type) => {
            stats[type].count++;
            updateMetaElem(stats[type].className, stats[type].count);
            stats.processed.count++;
            updateMetaElem(
                stats.processed.className,
                stats.processed.count,
                this.casesSelectedCount
            );
        }
        const fetchReasonAndMessagess = (data) => {
            let reasons = [];
            let html = '';
            data.forEach(elem => {
                if (elem.format == 'message') {
                    html += elem.contents;
                    const fragment = document.createDocumentFragment();
                    fragment.append(createElement('div', {
                        html: elem.contents
                    }));
                    reasons.push(
                        fragment.querySelector('.msg > .text').textContent
                    );
                }
            });
            return [reasons.at(-1), html];
        }
        const evaluateResponseValue = (value, catId, caseId) => {
            // Pass
            if (
                !value.length
                || value.at(-1)['contents'] == 'ok'
            ) {
                this.markPass(catId, caseId);
                process('passed');
            // Fail
            } else {
                this.markError(
                    catId, caseId, ...fetchReasonAndMessagess(value)
                );
                process('failed');
            }
        }
        const executeChunk = () => {
            const remaining = (selectedCases.length - chunkOffset);
            const myPromises = [];
            const innerPromises = [];
            let i;
            let until = Math.min(remaining, chunkSize);
            for (i = 0; i < until; i++) {
                const caseIndex = (i + chunkOffset);
                const caseItem = selectedCases[caseIndex];
                const caseId = caseItem.dataset.caseId;
                const [promise, abortController, catId]
                    = this.caseItemToFetchPromise(caseItem);
                myPromises.push(promise);
                this.abortControllers.add(abortController);
                promise.then(response => {
                    const contentPromise = response.json();
                    innerPromises.push(contentPromise);
                    contentPromise.then(value => {
                        evaluateResponseValue(value, catId, caseId);
                    }).catch(error => {
                        this.markError(catId, caseId, error.message);
                        process('failed');
                    }).finally(() => {
                        abortController.abort();
                    });
                }).catch(error => {
                    this.markError(catId, caseId, error.message);
                    process('failed');
                });
            }
            Promise.allSettled(myPromises).then(() => {
                Promise.allSettled(innerPromises).then(() => {
                    chunkOffset += i;
                    if (remaining >= chunkSize && this.state != 'aborted') {
                        executeChunk();
                    } else {
                        // Tests completed.
                        this.changeState('complete');
                        console.log(`Tests completed.`
                        + ` Processed: ${stats.processed.count}`
                        + `/${this.casesSelectedCount}.`
                        + ` Passed: ${stats.passed.count}.`
                        + ` Failed: ${stats.failed.count}`);
                    }
                });
            }).catch(error => {
                console.error(error);
            });
        }
        executeChunk();
    }
    resetAllResults(resetSelected = true) {
        this.changeState('pending');
        if (resetSelected) {
            this.group.querySelectorAll(':scope > .sel').forEach(catItem => {
                catItem.classList.remove('sel');
            });
            this.updateStateCount(0, 0);
        }
        let queryStr = ':scope > [data-state]';
        this.group.querySelectorAll(queryStr).forEach(catItem => {
            catItem.removeAttribute('data-state');
            catItem.removeAttribute('data-processed-count');
            catItem.removeAttribute('data-failed-count');
            catItem.querySelector(':scope > .cnt > .fail-cn')?.remove();
            const stateElem = catItem.querySelector(':scope > .cnt > .state');
            stateElem.setAttribute('role', 'button');
            stateElem.setAttribute('tabindex', '0');
            catItem.querySelectorAll(':scope > ol > li').forEach(caseItem => {
                caseItem.removeAttribute('data-state');
                caseItem.querySelector(':scope > .err')?.remove();
                queryStr = ':scope > .code-msg';
                caseItem.querySelectorAll(queryStr).forEach(elem => {
                    elem.remove();
                })
            });
        });
        this.header.querySelectorAll('.stats > .run').forEach(elem => {
            elem.nextElementSibling.remove();
            elem.remove();
        });
    }
}

function Popup(
    trigger,
    contentElem,
    relElem,
    offsetPos = 'BL',
    closeOnAnyButtonClick = true,
    closeOnTrigger = false
) {
    this.isOpen = false;
    const container = createElement('div', {
        classes: ['pp'],
    });
    const closeButton = createElement('button', {
        classes: ['cl-btn'],
        text: "Close Popup"
    });
    closeButton.addEventListener('click', () => {
        this.close();
    });
    container.append(closeButton, contentElem);
    this.container = document.body.appendChild(container);
    const offsets = getTopLeftOffset(offsetPos, relElem, this.container);
    container.style.top = offsets[0] + 'px';
    container.style.left = offsets[1] + 'px';
    this.isOpen = true;
    this.trigger = trigger;
    this.bindedCloseFunction = this.listener.bind(this);
    document.addEventListener('click', this.bindedCloseFunction, true);
    if (closeOnAnyButtonClick) {
        this.bindAllButtonsToClose();
    }
    if (closeOnTrigger) {
        trigger.addEventListener('click', () => {
            this.close();
        }, {once: true});
    }
}

Popup.prototype.listener = function(e) {
    if (
        !this.isEnclosedIn(e.target, this.container)
        && !this.isEnclosedIn(e.target, this.trigger)
    ) {
        this.close();
    }
};

Popup.prototype.close = function() {
    if (!this.isOpen) {
        return null;
    }
    this.container.style.visibility = 'hidden';
    this.container.remove();
    this.isOpen = false;
    document.removeEventListener('click', this.bindedCloseFunction, true);
};

Popup.prototype.isEnclosedIn = function(elem, wrapper) {
    while (elem && elem !== wrapper) {
        elem = elem.parentElement;
    }
    return !!elem;
};

Popup.prototype.bindAllButtonsToClose = function() {
    const buttons = this.container.getElementsByTagName('button');
    for (const button of buttons) {
        button.addEventListener('click', () => {
            this.close();
        });
    }
};

/**
 * Reference to the object that is currently the loaded screen
 * @type {object}
 */
let currentLoadedScreen;

/**
 * Object containing parameters that describe the current state
 * @type {object}
 */
let stateObj = {};

/**
 * Object containing connection data (eg. endpoint list, etc.)
 * @type {object}
 */
let connectData;

/**
 * An abstract screen object that each special screen should inherit
 * @type {object}
 */
const genericScreen = {
    container: null,
    isLoaded: false,
    internalParams: [],
    onCloseCallbacks: new Set(),
    queryParams: [
        'project',
        'side',
        'main',
        'project_search_query',
        'file_search_query',
    ],
    menuItems: [
        {
            title: `About ${appName}`,
            event: function() {
                const template = document.getElementById('about-lwis-template');
                const fragment = template.content.cloneNode(true);
                const upgradeStatus = fragment.querySelector('.upg-sts');
                let onClose;
                let requestValid = true;
                const checkVersion = () => {
                    upgradeStatus.dataset.status = 'pending';
                    const timeout = 3000;
                    const signal = AbortSignal.timeout(timeout);
                    getLatestAppVersion(signal).then(version => {
                        if (requestValid) {
                            if (version == appVersion) {
                                upgradeStatus.dataset.status = 'ok';
                            } else {
                                upgradeStatus.dataset.status = 'upgrade';
                                const upgradeMsg = upgradeStatus.querySelector(
                                    '.upg'
                                );
                                upgradeMsg.innerHTML = upgradeMsg.innerHTML
                                    .replace('{version}', version);
                            }
                        }
                    }).catch(error => {
                        upgradeStatus.dataset.status = 'error';
                        console.error("Could not check latest version", error);
                    });
                    onClose = () => {
                        requestValid = false;
                    }
                }
                checkVersion();
                raiseDialogWith(fragment.children, null, ['abt-lwis'], onClose);
            },
            buttonClassList: ['show-info-btn'],
        }, {
            title: "Toggle fullscreen",
            event: function() {
                if (!isFullscreen()) {
                    enterFullscreen();
                } else {
                    exitFullscreen();
                }
            },
            buttonClassList: ['tgl-fscr-btn'],
        }, {
            title: "Toggle between OS, light, and dark color modes",
            text: "Toggle color modes",
            event: function() {
                switchOnNextColorMode();
            },
            buttonClassList: ['tgl-clr-mode'],
        },
    ],
    abortControllers: new Set(),
    /**
     * Amends the location to contain given state parameters
     * @param {object} params - State parameters
     * @returns {URL|null} - Null when no state parameters where published into
     *     the URL
     */
    publishParamsIntoURL(params) {
        const toPublish = Object.create(null);
        for (const [key, val] of Object.entries(params)) {
            if (this.queryParams.includes(key)) {
                toPublish[key] = val;
            }
        }
        return amendLocationUrl(toPublish);
    },
    /**
     * Removes all or chosen parameters from state object and location
     * @param {array} chosenParams A list of params to remove
     */
    releaseParams(chosenParams) {
        for (const [key] of Object.entries(stateObj)) {
            if (
                (this.queryParams.includes(key)
                || this.internalParams.includes(key))
                && (!chosenParams || chosenParams.includes(key))
            ) {
                delete stateObj[key];
            }
        }
        const url = new URL(location);
        let urlParams = new URLSearchParams(url.search);
        for (const [key] of urlParams.entries()) {
            if (
                this.queryParams.includes(key)
                && (!chosenParams || chosenParams.includes(key))
            ) {
                url.searchParams.delete(key);
            }
        }
        console.log('push state', stateObj, url.toString());
        history.pushState(stateObj, '', url);
    },
    /**
     * Sets and saved state parameters
     * @param {object} params - State parameters to set
     * @param {string} stateAction - "push" or "replace"
     */
    setParams(params, stateAction = 'push') {
        const toPublishInUrl = Object.create(null);
        for (const [key, val] of Object.entries(params)) {
            if (this.queryParams.includes(key)) {
                toPublishInUrl[key] = val;
            }
            const sourceObj = Object.create(null);
            sourceObj[key] = val;
            stateObj = {...stateObj, ...sourceObj};
        }
        let url = amendLocationUrl(toPublishInUrl);
        if (!url) {
            url = new URL(location);
        }
        if (stateAction == 'push') {
            console.log('push state', stateObj, url.toString());
            history.pushState(stateObj, '', url);
        } else {
            console.log('replace state', stateObj, url.toString());
            history.replaceState(stateObj, '', url);
        }
    },
    /**
     * Builds screen wrappers - screen container, toolbar, body, body inner
     * @returns {array} - Array with screen container and screen inner wrapper
     */
    buildWrappers() {
        const container = createElement('div', {
            classes: ['scr', this.name],
            attrs: {
                'data-screen-name': this.name,
            }
        });
        container.append(this.buildToolbar());
        const screenBody = createElement('div', {
            classes: ['scr-bd'],
        });
        container.append(screenBody);
        const screenInner = createElement('div', {
            classes: ['scr-inr'],
        });
        screenBody.append(screenInner);
        return [container, screenInner];
    },
    /**
     * Registers a given callback function to be called on screen close
     * @param {callable} callback - A callback function
     */
    onClose(callback) {
        this.onCloseCallbacks.add(callback);
    },
    /**
     * Closes currently active screen
     * @returns {null|boolean} - Null when there is nothing to be closed
     */
    close() {
        if (!this.isLoaded) {
            return null;
        }
        console.log(`closing ${this.name} screen`);
        this.clearAbortControllers();
        this.container.remove();
        document.body.removeAttribute('data-current-screen-name');
        this.isLoaded = false;
        if (this.onCloseCallbacks.size) {
            this.onCloseCallbacks.forEach(callback => {
                callback();
            });
        }
        return true;
    },
    /**
     * Adds the screen container to the document body and makes it globally
     *     available
     * @param {HTMLDivElement} container - Screen container
     */
    wrapperToBody(container) {
        this.container = document.body.insertAdjacentElement(
            'afterbegin',
            container
        );
        this.toolbar = this.container.querySelector('.scr-tb');
        document.body.setAttribute('data-current-screen-name', this.name);
    },
    /**
     * Run final tasks after finishing screen loading
     */
    finishLoading() {
        this.isLoaded = true;
        currentLoadedScreen = this;
    },
    /**
     * Excavates a document fragment from the corresponding screen template
     * @returns {DocumentFragment}
     */
    extractFromTemplate() {
        const queryStr = `template[data-screen-name="${this.name}"]`;
        const template = document.querySelector(queryStr);
        return template.content.cloneNode(true);
    },
    /**
     * Looks for elements with class name "scr-cl-btn" and makes them screen
     *     close triggers
     * @param {HTMLDivElement} container - Screen container
     */
    bindCloseButtons(container) {
        const buttons = container.getElementsByClassName('scr-cl-btn');
        for (const button of buttons) {
            button.addEventListener('click', () => {
                this.releaseParams();
                landingScreen.load();
            });
        }
    },
    /**
     * Looks for elements with class name "scr-fscr-btn" and makes them
     *     fullscreen mode triggers
     * @param {HTMLDivElement} container - Screen container
     */
    bindFullscreenButtons(container) {
        const buttons = container.getElementsByClassName('scr-fscr-btn');
        for (const button of buttons) {
            button.addEventListener('click', () => {
                enterFullscreen();
            });
        }
    },
    /**
     * Builds screen toolbar
     * @returns {HTMLDivElement} - The screen toolbar container
     */
    buildToolbar() {
        const toolbar = createElement('div', {
            classes: ['tb', 'scr-tb'],
            id: 'screen-toolbar',
        });
        const menu = createElement('menu', {
            classes: ['scr-menu'],
        });
        const menuItems = [
            ...this.menuItems,
            ...genericScreen.menuItems
        ];
        // Sort by position.
        menuItems.sort((a, b) => {
            if (!Object.hasOwn(a, 'position') ) {
                a.position = 0;
            }
            if (!Object.hasOwn(b, 'position')) {
                b.position = 0;
            }
            if (a.position < b.position) {
                return -1;
            } else if (a.position > b.position) {
                return 1;
            }
            return 0;
        });
        menuItems.forEach(item => {
            const button = createElement('button', {
                text: item.text || item.title,
                classes: item.buttonClassList,
                title: item.title,
            });
            menu.append(button);
            if (Object.hasOwn(item, 'event')) {
                button.addEventListener('click', () => {
                    item.event.call(this, button, menu);
                });
            }
            if (Object.hasOwn(item, 'build')) {
                item.build.call(this, button, menu);
            }
        });
        toolbar.append(menu);
        return toolbar;
    },
    /**
     * Instantiates a new abort controller and adds it to the set of active
     *     abort controllers
     * @returns {AbortController} - Granted abort controller
     */
    provideAbortController(timeout) {
        const abortController = new AbortController();
        this.abortControllers.add(abortController);
        /* At the time of writing the static `AbortSignal.timeout()` is not
        sufficient to fulfill on needs, because it returns an abort signal,
        which cannot be manually aborted. */
        let timeoutHandlerId;
        if (timeout) {
            timeoutHandlerId = setTimeout(() => {
                if (!abortController.signal.aborted) {
                    abortController.abort(new RequestTimeoutError(
                        `Request timeout error`
                    ));
                }
            }, timeout);
        }
        // A generic Event with no added properties.
        abortController.signal.addEventListener('abort', () => {
            if (timeoutHandlerId) {
                clearTimeout(timeoutHandlerId);
            }
            this.abortControllers.delete(abortController);
            console.log(`delete abort controller in ${this.name}`);
        });
        return abortController;
    },
    /**
     * Abort all abort controllers, that haven't aborted yet and truncate the
     *     abort controllers set
     */
    clearAbortControllers() {
        // It's a "Set" object.
        if (this.abortControllers.size) {
            const size = this.abortControllers.size;
            console.log(`has abort controllers to clear: ${size}`);
            this.abortControllers.forEach(abortController => {
                if (!abortController.signal.aborted) {
                    abortController.abort(
                        new ScreenCloseException(`Closing screen ${this.name}`)
                    );
                }
            });
            // Though this should be empty by now.
            this.abortControllers.clear();
        }
    },
    /**
     * Moves children elements from a template toolbar into the screen toolbar
     * @param {HTMLElement} container - Current screen's container
     */
    adoptToolbarContents(container) {
        const toolbar = container.querySelector('.scr-tb');
        const menu = container.querySelector('.scr-menu');
        const containerToolbar = container.querySelector('#toolbar');
        for (const child of containerToolbar.children) {
            toolbar.insertBefore(child, menu);
        }
        containerToolbar.remove();
    },
    /**
     * Build base structure for the "show options" button
     * @param {string} text - Button's text content
     * @returns {HTMLButtonElement}
     */
    createOptionsButton(text = "Opts", title = "Show options") {
        return createElement('button', {
            text,
            classes: ['opt-btn'],
            title,
        });
    },
    /**
     * Populate search field param into the URL query params
     * @param {HTMLInputElement} searchField - Input search field
     */
    searchFieldInputCallback(searchField) {
        if (searchField.value !== '') {
            let params = {};
            params[searchField.name] = searchField.value;
            this.setParams(params);
        } else {
            this.releaseParams([searchField.name]);
        }
    }
};

/**
 * Screen which appears when connected to API
 * @type {object}
 */
const landingScreen = {
    name: 'landing',
    menuItems: [
        {
            title: "Reconnect",
            event: function() {
                welcomeScreen.reconnect();
            },
            buttonClassList: ['rcnt-btn'],
            position: 1
        }
    ],
    /**
     * Runs all tasks required to load the screen
     */
    async load() {
        connectData = welcomeScreen.getConnectData();
        const [screenContainer, screenInner] = super.buildWrappers();
        screenInner.classList.add('wtng');
        if (currentLoadedScreen) {
            currentLoadedScreen.close();
        }
        apiRequest(
            connectData.projectsListing,
            this.provideAbortController()
        ).then(payload => {
            if (
                typeof payload === 'object'
                && Array.isArray(payload.data)
            ) {
                const data = payload.data;
                if (data.length) {
                    const templateFragment = this.extractFromTemplate();
                    screenInner.append(templateFragment);
                    new SearchableApiListing(
                        screenInner.querySelector('.lst'),
                        connectData.projectsListing,
                        (entry, groupItem) => {
                            const button = createElement('button', {
                                text: entry.title,
                            });
                            button.addEventListener('click', () => {
                                managerScreen.load({
                                    project: entry.title,
                                });
                            });
                            groupItem.append(button);
                            return groupItem;
                        },
                        this.provideAbortController.bind(this),
                        payload,
                        1,
                        null,
                        super.searchFieldInputCallback.bind(this)
                    );
                } else {
                    messageScreen.load({
                        message: `No projects found. Qualified projects must`
                        + ` contain a system ".config" directory with a`
                        + ` properly set up file`
                        + ` "project-directory-config.php" inside it.`,
                        buttons: [{
                            title: "Retry",
                            action: () => {
                                const message = "Rechecked projects";
                                shortNotifications.send(message);
                                this.releaseParams();
                                landingScreen.load();
                            },
                        }, {
                            title: "Reconnect",
                            action: () => {
                                welcomeScreen.reconnect();
                            }
                        }]
                    });
                }
            } else {
                console.error("Invalid payload type", payload);
            }
        }).catch(error => {
            console.error(error);
        }).finally(() => {
            screenInner.classList.remove('wtng');
        });
        super.wrapperToBody(screenContainer);
        super.finishLoading();
        return screenInner;
    },
    /**
     * Closes the screen
     */
    close() {
        super.close();
        this.releaseParams([
            'project_search_query'
        ]);
    },
};

/**
 * A screen that displays a standalone message (usually a system message)
 * @type {object}
 */
const messageScreen = {
    name: 'message',
    menuItems: [
        {
            title: "Reconnect",
            event: function() {
                welcomeScreen.reconnect();
            },
            buttonClassList: ['rcnt-btn'],
            position: 1
        }
    ],
    /**
     * Runs all tasks required to load the screen
     * @param {object} params - Params to use when loading
     */
    async load(params) {
        const [screenContainer, screenInner] = super.buildWrappers();
        const article = createElement('article', {
            classes: ['msg'],
        });
        const paragraph = createElement('p', {
            text: params.message,
        });
        article.append(paragraph);
        if (params.buttons && params.buttons.length) {
            const menu = createElement('menu');
            for (const data of params.buttons) {
                const button = createElement('button', {
                    text: data.title,
                });
                button.addEventListener('click', () => {
                    if (data.screen) {
                        this.releaseParams();
                        data.screen.load(data.params || {});
                    } else if (data.action) {
                        data.action();
                    }
                });
                menu.append(button);
            }
            article.append(menu);
        }
        screenInner.append(article);
        if (currentLoadedScreen) {
            currentLoadedScreen.close();
        }
        super.wrapperToBody(screenContainer);
        super.finishLoading();
    }
};

/**
 * The main application screen where project files and data will be managed
 * @type {object}
 */
const managerScreen = {
    name: 'manager',
    favoritesStoreIndexName: 'stonetable_favorites',
    menuItems: [
        {
            title: "Close screen",
            event: function() {
                this.releaseParams();
                landingScreen.load();
            },
            buttonClassList: ['cl-btn', 'scr-cl-btn'],
            position: 1,
        }
    ],
    onMainPanelCloseCallbacks: new Set(),
    /**
     * Runs all tasks required to load the screen
     * @param {object} params - Params to use when loading
     * @param {boolean} synthetic - Whether to set params synthetically
     */
    async load(params, synthetic = false) {
        if (!Object.hasOwn(params, 'project')) {
            return null;
        }
        const [screenContainer, screenInner] = super.buildWrappers();
        screenInner.classList.add('wtng');
        if (currentLoadedScreen) {
            currentLoadedScreen.close();
        }
        isLocalServer = connectData.isLocalServer;
        const url = new URL(connectData.projectFind);
        url.searchParams.set('project_name', params.project);
        apiRequest(url, this.provideAbortController()).then(payload => {
            // Received project data.
            if (payload !== false && payload.data) {
                this.loadedProjectName = params.project;
                const data = payload.data;
                this.loadedProjectData = data;
                console.log(`loaded project ${data.title}`, data);
                const templateFragment = this.extractFromTemplate();
                screenInner.append(templateFragment);
                // Binds inside screen inner.
                this.bindCloseButtons(screenInner);
                this.bindFullscreenButtons(screenInner);
                this.adoptToolbarContents(screenContainer);
                shortNotifications.send(
                    params.project + " loaded"
                );
                this.sidebar = screenInner.querySelector('#sidebar');
                this.mainPanel = screenInner.querySelector('#main-panel');
                appTitleParts.unshift(params.project);
                resetAppTitle();
                this.setParams(params, (!synthetic) ? 'push' : null);
                let sidePath;
                if (Object.hasOwn(params, 'side')) {
                    sidePath = params.side;
                } else {
                    let queryStr = '.cat-menu button';
                    const firstButton = this.toolbar.querySelector(queryStr);
                    const paramsFromSearch = Object.fromEntries(
                        (new URLSearchParams(firstButton.dataset.query))
                            .entries()
                    );
                    this.setParams(paramsFromSearch, 'replace');
                    sidePath = paramsFromSearch.side;
                }
                const url = new URL(connectData.directoryListing);
                url.searchParams.set('project_path', data.pathname);
                url.searchParams.set('path', sidePath);
                this.listing = new SearchableApiListing(
                    screenInner.querySelector('.lst'),
                    url,
                    this.buildFileListingItem.bind(this),
                    this.provideAbortController.bind(this),
                    null,
                    0,
                    function(payload) {
                        // Adds the "go to parent directory" button.
                        if (
                            Object.hasOwn(payload, 'parentDir')
                            && !this.listingGroup.firstElementChild?.classList.contains('dir-prt')
                        ) {
                            let item = createElement('div', {
                                classes: ['dir-prt'],
                            });
                            payload.parentDir.basename = '..';
                            item = this.entryBuilder.call(
                                this, payload.parentDir, item, false
                            );
                            this.listingGroup.prepend(item);
                        }
                    },
                    (searchField) => {
                        if (searchField.value !== '') {
                            let params = {};
                            params[searchField.name] = searchField.value;
                            this.setParams(params);
                        } else {
                            this.releaseParams([searchField.name]);
                        }
                    }
                );
                const layoutTogglers
                    = screenContainer.querySelectorAll('.tgl-lo');
                for (const layoutToggler of layoutTogglers) {
                    layoutToggler.addEventListener('click', () => {
                        const attrName = 'data-layout';
                        const layout
                            = document.body.getAttribute(attrName);
                        if (layout === 'open') {
                            document.body.setAttribute(attrName, 'discrete');
                        } else if (layout === 'discrete') {
                            document.body.setAttribute(attrName, 'open');
                            this.listing.fillUpContents();
                        }
                    });
                }
                const hasMain = Object.hasOwn(params, 'main');
                if (hasMain) {
                    this.loadIntoMainPanel(params.main);
                } else {
                    this.putOnNoFileMessage();
                }
                const catMenu = this.toolbar.querySelector('.cat-menu');
                const catItems = catMenu.querySelectorAll(':scope > ul > li');
                for (const catItem of catItems) {
                    if (catItem.firstElementChild.matches('button')) {
                        this.bindQueryButton(catItem.firstElementChild);
                    }
                    let queryStr = ':scope > ul';
                    const nestedList = catItem.querySelector(queryStr);
                    if (nestedList) {
                        const optionsButton = this.createOptionsButtonFor(
                            nestedList,
                            catItem
                        );
                        for (const child of nestedList.children) {
                            if (child.firstElementChild.matches('button')) {
                                this.bindQueryButton(child.firstElementChild);
                            }
                        }
                        catItem.append(optionsButton);
                        catItem.classList.add('has-opt');
                        nestedList.remove();
                    }
                }
                this.loadProjectFavorites();
                if (hasMain && isCompact.matches) {
                    document.body.setAttribute('data-layout', 'discrete');
                }
            // Project not found.
            } else if (payload !== false && !payload.data) {
                messageScreen.load({
                    message: "Project \""
                    + params.project
                    + "\" not found. Qualified"
                    + " projects must contain a system \".config\""
                    + " directory with a properly set up file"
                    + " \"project-directory-config.php\" inside it.",
                    buttons: [{
                        title: "Retry",
                        action: () => {
                            const message = "Rechecked project";
                            shortNotifications.send(message);
                            managerScreen.load(params);
                        },
                    }, {
                        title: "Show Project List",
                        action: () => {
                            this.releaseParams();
                            landingScreen.load();
                        }
                    }]
                });
            // No payload.
            } else {
                console.error(`No payload`);
            }
        }).catch(error => {
            console.error(error);
        }).finally(() => {
            screenInner.classList.remove('wtng');
        });
        super.wrapperToBody(screenContainer);
        super.finishLoading();
    },
    /**
     * Closes this screen
     */
    close() {
        if (super.close()) {
            appTitleParts.shift();
            resetAppTitle();
        }
    },
    /**
     * Empties the main panel
     */
    truncateMainPanel() {
        this.mainPanel.innerHTML = '';
    },
    /**
     * Loads file listing into the side panel
     * @param {string} path - Side path name
     * @param {boolean} setParams - Whether to set path name in location
     */
    loadIntoSidePanel(path, setParams = true) {
        document.body.setAttribute('data-layout', 'open');
        // Close favorites in case they are open.
        const favMenu = this.toolbar.querySelector('.fav-menu');
        if (favMenu) {
            favMenu.classList.remove('open');
        }
        const url = new URL(connectData.directoryListing);
        url.searchParams.set('project_path', this.loadedProjectData.pathname);
        url.searchParams.set('path', path);
        this.listing.resetData(url).then(() => {
            if (setParams) {
                this.setParams({
                    side: path
                });
            }
            this.currentSidePath = path;
        });
    },
    /**
     * Loads file or other entity into the main panel
     * @param {string} path - File's path name
     * @param {bool} setParams - Whether to set path name in location
     */
    loadIntoMainPanel(path, setParams = true) {
        if (
            this.mainPanelAbortController
            && !this.mainPanelAbortController.signal.aborted
        ) {
            this.mainPanelAbortController.abort();
        }
        this.removeMainPanelMasterMessage();
        this.truncateMainPanel();
        this.mainPanel.removeAttribute('data-handler');
        const waitingTimeout = setTimeout(() => {
            this.mainPanel.classList.add('wtng');
        }, 150);
        if (isCompact.matches) {
            document.body.setAttribute('data-layout', 'discrete');
        }
        let url;
        // Any common path.
        if (path !== 'unit-tests') {
            url = new URL(connectData.fileHandler);
            url.searchParams.set(
                'project_path',
                this.loadedProjectData.pathname
            );
            url.searchParams.set('path', path);
        // The special, reserved "unit-tests" path.
        } else {
            url = new URL(connectData.unitTests);
            url.searchParams.set(
                'project_path',
                this.loadedProjectData.pathname
            );
        }
        this.mainPanelAbortController = this.provideAbortController();
        apiRequest(url, this.mainPanelAbortController).then(payload => {
            if (payload !== false) {
                const data = payload.data;
                const mainPanelToolbar = createElement('div', {
                    classes: ['tb'],
                });
                const mainPanelBody = createElement('div', {
                    classes: ['bd'],
                });
                const mainPanelToolbarInner = createElement('div', {
                    classes: ['inr'],
                });
                mainPanelToolbarInner.append(this.buildMainPanelBreadcrumbsMenu(
                    data.meta
                ));
                mainPanelToolbarInner.append(this.buildMainPanelControllersMenu(
                    data.meta
                ));
                mainPanelToolbar.append(mainPanelToolbarInner);
                this.mainPanel.append(mainPanelToolbar, mainPanelBody);
                this.mainPanelToolbar = mainPanelToolbar;
                this.mainPanelBody = mainPanelBody;
                switch (data.meta.handlerName) {
                    case 'source-code':
                        this.populateCodeLines(data.parts, data.meta.lineCount);
                        break;
                    case 'demo-output':
                        this.populateDemoData(data.parts);
                        break;
                    case 'unit-tests':
                        this.populateUnitTestsData(data.parts, data.meta);
                        break;
                    default:
                        raiseDialogMessage(
                            `Unrecognized handler ${data.meta.handlerName}`
                        );
                        break;
                }
                this.mainPanel.setAttribute(
                    'data-handler',
                    data.meta.handlerName
                );
                if (setParams) {
                    this.setParams({
                        main: path,
                    });
                }
                this.currentMainPath = path;
            } else {
                this.putOnNoFileMessage();
                removeLocationParams(['main']);
            }
        }).catch(error => {
            console.error(error);
            raiseDialogMessage(`Could not load file: ${error.message}`);
            this.putOnNoFileMessage();
            removeLocationParams(['main']);
        }).finally(() => {
            clearTimeout(waitingTimeout);
            this.mainPanel.classList.remove('wtng');
        });
    },
    /**
     * Registers a given callback function to be called on file close
     * @param {callable} callback - A callback function
     */
    onMainPanelClose(callback) {
        this.onMainPanelCloseCallbacks.add(callback);
    },
    /**
     * Raises the master main panel message
     * @param {string} text - Message text
     */
    putOnMainPanelMessage(text) {
        this.mainPanel.append(createSimpleMessageElement(text));
    },
    /**
     * Raises a message for when no file is selected
     */
    putOnNoFileMessage() {
        this.putOnMainPanelMessage(`No File Selected`);
    },
    /**
     * Removes main panel's master message element
     */
    removeMainPanelMasterMessage() {
        this.mainPanel.querySelector(':scope > .msg')?.remove();
    },
    /**
     * Raises a master message in the main panel's body area
     * @param {string} text - Message text
     */
    putOnMainPanelBodyMessage(text) {
        this.mainPanelBody.append(createSimpleMessageElement(text));
    },
    /**
     * Populates code lines into the main panel
     * @param {object} lines - Object containing line data
     * @param {number} linesCount - Total number of lines
     */
    populateCodeLines(lines, linesCount) {
        const classes = ['grp', 'code-lns', 'code-php'];
        if (document.body.classList.contains('word-wrap')) {
            classes.push('word-wrap');
        }
        const list = createElement('div', {
            classes,
        });
        for (let lineNum = 1; lineNum <= linesCount; lineNum++) {
            const item = createElement('div');
            const lineNumberContainer = createElement('div', {
                classes: ['line-num'],
                text: lineNum,
            });
            const lineContentContainer = createElement('div', {
                classes: ['cnt'],
            });
            if (Object.hasOwn(lines, lineNum)) {
                lineContentContainer.innerHTML = lines[lineNum];
                const clickableElemAttr = 'data-relative-path';
                let queryStr = '[' + clickableElemAttr + ']';
                const clickableElems
                    = lineContentContainer.querySelectorAll(queryStr);
                for (const clickableElem of clickableElems) {
                    const project = clickableElem.getAttribute('data-project');
                    const sameProject
                        = (project && this.loadedProjectName == project);
                    clickableElem.addEventListener('click', () => {
                        if (!sameProject) {
                            const params = {
                                project,
                                main: clickableElem.getAttribute(
                                    clickableElemAttr
                                ),
                            };
                            const onClick = () => {
                                const url = removeParamsFromUrl(
                                    amendLocationUrl(params),
                                    ['side']
                                );
                                open(url.toString(), '_blank');
                            }
                            raiseDialogMessage(
                                `Do you want to jump to project "${project}"?`,
                                {
                                    'no': {
                                        text: "No",
                                        autofocus: true,
                                    },
                                    'yes': {
                                        text: "Yes",
                                    },
                                    'yes-but': {
                                        text: "Yes, But in New Tab",
                                        onClick,
                                    }
                                },
                                [], // Dialog classes.
                                (returnValue) => {
                                    if (returnValue === 'yes') {
                                        managerScreen.load(params);
                                    }
                                }
                            );
                        } else {
                            this.loadIntoMainPanel(
                                clickableElem.getAttribute(clickableElemAttr)
                            );
                        }
                    });
                    clickableElem.setAttribute('role', 'button');
                    clickableElem.setAttribute('tabindex', '0');
                    let title = `Navigate to`
                        + ` ${clickableElem.dataset.relativePath}`;
                    if (!sameProject) {
                        title += ` in project "${project}"`;
                    }
                    clickableElem.title = title;
                }
            }
            item.append(lineNumberContainer, lineContentContainer);
            list.append(item);
        }
        if (list.children.length) {
            this.mainPanelBody.append(list);
        } else {
            this.putOnMainPanelBodyMessage(`Empty Output`);
        }
    },
    /**
     * Populates demo data into the main panel
     * @param {object} data - Data payload
     */
    populateDemoData(data) {
        if (data.length) {
            for (const {format, contents} of data) {
                if (format === 'output') {
                    const container = createElement('div', {
                        classes: ['o'],
                    });
                    container.innerHTML = contents;
                    this.mainPanelBody.append(container);
                } else {
                    this.mainPanelBody.insertAdjacentHTML(
                        'beforeend',
                        contents
                    );
                }
            }
        // No data parts.
        } else {
            this.putOnMainPanelBodyMessage(`Empty Output`);
        }
    },
    /**
     * Populates unit test listing into the main panel
     * @param {object} data - Data payload
     * @param {object} meta - Info about the data payload
     */
    populateUnitTestsData(data, meta) {
        if (meta.total > 0) {
            const instance = new UnitTestsListing(data, meta, this);
            this.mainPanelBody.append(instance.wrapper);
        // No unit tests found.
        } else {
            this.putOnMainPanelBodyMessage(`No Unit Tests`);
        }
    },
    /**
     * Builds breadcrumb menu for the main panel
     * @param {object} fileData Main open file metadata
     * @returns {HTMLMenuElement} Menu element
     */
    buildMainPanelBreadcrumbsMenu(fileData) {
        const menu = createElement('menu', {
            classes: ['brcr-menu'],
        });
        const currentButton = createElement('button', {
            text: fileData.basename,
            title: "Reload file",
        });
        currentButton.addEventListener('click', () => {
            this.reloadMainPanel();
        });
        menu.append(currentButton);
        return menu;
    },
    /**
     * Builds control menu for the main panel
     * @param {object} fileData - Main open file metadata
     * @returns {HTMLMenuElement} Menu element
     */
    buildMainPanelControllersMenu(fileData) {
        const menu = createElement('menu', {
            classes: ['con-menu'],
        });
        // Unit tests page will not have an options menu, because it's not
        // really a file.
        if (fileData.handlerName !== 'unit-tests') {
            const optionsButton = this.createOptionsButtonFor(() => {
                return this.buildContextMenu(fileData);
            });
            menu.append(optionsButton);
        }
        const reloadButton = createElement('button', {
            classes: ['rld-btn'],
            text: "Reload",
            title: "Reload file",
        });
        reloadButton.addEventListener('click', () => {
            this.reloadMainPanel();
        });
        const closeButton = createElement('button', {
            classes: ['cl-btn', 'file-cl-btn'],
            text: "Close",
            title: "Close file",
        });
        closeButton.addEventListener('click', () => {
            this.closeMainPanel();
        });
        // Add "Word Wrap" button only if it's a source code file.
        if (fileData.handlerName === 'source-code') {
            const wordWrapButton = createElement('button', {
                classes: ['tgl-word-wrap-btn'],
                text: "Toggle word wrap",
                title: "Toggle word wrapping",
            });
            wordWrapButton.addEventListener('click', () => {
                const codeLines = this.mainPanelBody.querySelector('.code-lns');
                codeLines.classList.toggle('word-wrap');
                document.body.classList.toggle('word-wrap');
            });
            menu.append(wordWrapButton);
        }
        menu.append(reloadButton, closeButton);
        return menu;
    },
    /**
     * Reload main panel (toolbar and output contents)
     */
    reloadMainPanel() {
        if (this.currentMainPath) {
            this.loadIntoMainPanel(this.currentMainPath);
        }
    },
    /**
     * Closes main panel
     */
    closeMainPanel() {
        this.mainPanel.innerHTML = '';
        this.putOnNoFileMessage();
        this.releaseParams(['main']);
        if (this.onMainPanelCloseCallbacks.size) {
            this.onMainPanelCloseCallbacks.forEach(callback => {
                callback();
            });
        }
    },
    /**
     * Builds context menu
     * @param {object} data - Data to be used for context menu elements
     * @returns {HTMLMenuElement} - Menu element
     */
    buildContextMenu(data) {
        console.log(`build context menu`);
        const menu = createElement('menu');
        const listElem = createElement('ul');
        function hasDataProperty(property) {
            return (
                Object.hasOwn(data, property)
                && data[property] !== null
            );
        }
        function createListItem(text, clickCallback) {
            const listItem = createElement('li');
            const button = createElement('button', {
                text,
            });
            listItem.append(button);
            if (typeof clickCallback === 'function') {
                button.addEventListener('click', () => {
                    clickCallback.call(this, listItem);
                });
            }
            return listElem.appendChild(listItem);
        }
        if (hasDataProperty('pathname')) {
            createListItem("Copy Pathname", () => {
                writeToClipboard(data.pathname);
            });
        }
        if (hasDataProperty('basename')) {
            createListItem("Copy File Name", () => {
                writeToClipboard(data.basename);
            });
        }
        if (hasDataProperty('IdeUri') && isLocalServer) {
            createListItem("Open in IDE", () => {
                open(data.IdeUri, '_self', 'noopener');
            });
        }
        if (hasDataProperty('useLine')) {
            createListItem("Copy Use Line", () => {
                writeToClipboard(data.useLine);
            });
        }
        if (hasDataProperty('sourceFileRelativePathname')) {
            createListItem("Reveal Source File", () => {
                this.loadIntoMainPanel(data.sourceFileRelativePathname);
            });
        }
        if (hasDataProperty('sourceFileIdeUri') && isLocalServer) {
            createListItem("Open Source in IDE", () => {
                open(data.sourceFileIdeUri, '_self', 'noopener');
            });
        }
        function createDemoListItems(relativePathname, IdeUri) {
            if (relativePathname) {
                createListItem("Reveal Demo File", () => {
                    this.loadIntoMainPanel(relativePathname);
                });
            }
            if (IdeUri && isLocalServer) {
                createListItem("Open Demo File in IDE", () => {
                    open(IdeUri, '_self', 'noopener');
                });
            }
        }
        function createPlaygroundListItems(relativePathname, IdeUri) {
            if (relativePathname) {
                createListItem("Reveal Playground File", () => {
                    this.loadIntoMainPanel(relativePathname);
                });
            }
            if (isLocalServer) {
                createListItem("Open Playground in IDE", () => {
                    open(IdeUri, '_self', 'noopener');
                });
            }
        }
        if (hasDataProperty('playgroundFileCreateUrl')) {
            createListItem("Create Playground File", listItem => {
                fetch(data.playgroundFileCreateUrl).then(response => {
                    return response.json();
                }).then(payload => {
                    listItem.remove();
                    createPlaygroundListItems.call(
                        this,
                        payload.data.relativePathname,
                        payload.data.IdeUri
                    );
                    if (isLocalServer) {
                        open(payload.data.IdeUri, '_self', 'noopener');
                    }
                    this.loadIntoMainPanel(payload.data.relativePathname);
                }).catch(error => {
                    console.error(error);
                });
            });
        } else if (hasDataProperty('playgroundFilePathname')) {
            createPlaygroundListItems.call(
                this,
                data.playgroundFileRelativePathname,
                data.playgroundFileIdeUri
            );
        }
        if (hasDataProperty('demoFileCreateUrl')) {
            createListItem("Create Demo File", listItem => {
                fetch(data.demoFileCreateUrl).then(response => {
                    return response.json();
                }).then(payload => {
                    listItem.remove();
                    createDemoListItems.call(
                        this,
                        payload.data.relativePathname,
                        payload.data.IdeUri
                    );
                    if (isLocalServer) {
                        open(payload.data.IdeUri, '_self', 'noopener');
                    }
                    this.loadIntoMainPanel(payload.data.relativePathname);
                }).catch(error => {
                    console.error(error);
                });
            });
        } else if (hasDataProperty('demoFilePathname')) {
            createDemoListItems.call(
                this,
                data.demoFileRelativePathname,
                data.demoFileIdeUri
            );
        }
        if (hasDataProperty('staticFilePathname')) {
            createDemoListItems.call(
                this,
                data.staticFileRelativePathname,
                data.staticFileIdeUri
            );
        }
        const favoriteObj = {
            title: data.basename,
            place: (data.type === 'file')
                ? 'main'
                : 'side',
            path: data.relativePathname,
            data
        };
        const favoriteExists = this.favoriteExists(
            favoriteObj.place,
            favoriteObj.path
        );
        if (
            favoriteExists === false
            && (
                !Object.hasOwn(data, 'isSupportedFileType')
                || data.isSupportedFileType
            )
        ) {
            createListItem("Add to Favorites", listItem => {
                const add = this.addToFavorites(
                    ...Object.values(favoriteObj)
                );
                if (add === true) {
                    listItem.remove();
                }
            });
        }
        if (hasDataProperty('url')) {
            createListItem("Open URL in New Tab", () => {
                open(data.url, '_blank');
            });
        }
        if (hasDataProperty('rebuildSpecialCommentsUrl') ) {
            createListItem("Rebuild Special Comments", () => {
                apiRequest(data.rebuildSpecialCommentsUrl).then(payload => {
                    if (payload !== false) {
                        shortNotifications.send(
                            `Special comments rebuilt in ${data.basename}`
                        );
                        if (this.currentMainPath === data.relativePathname) {
                            this.reloadMainPanel();
                        }
                    }
                });
            });
        }
        menu.append(listElem);
        return menu;
    },
    /**
     * Complement file listing item
     * @param {object} data - Item metadata
     * @param {HTMLElement} item - Item shell
     * @param {boolean} includeOptionsButton - Whether to include options button
     * @returns {HTMLElement} - Item with appended elements
     */
    buildFileListingItem(data, item, includeOptionsButton = true) {
        item.classList.add('type-' + data.type);
        const isClickable = data.type === 'dir'
        || (
            Object.hasOwn(data, 'isSupportedFileType')
            && (data.isSupportedFileType || isLocalServer)
        );
        const tagName = (isClickable) ? 'button' : 'span';
        const mainButton = createElement(tagName, {
            text: data.basename,
            title: data.basename,
        });
        item.append(mainButton);
        if (isClickable) {
            mainButton.addEventListener('click', () => {
                if (data.type === 'file') {
                    if (data.isSupportedFileType) {
                        this.loadIntoMainPanel(data.relativePathname);
                    } else {
                        open(data.IdeUri, '_self', 'noopener');
                    }
                } else if (data.type === 'dir') {
                    this.loadIntoSidePanel(data.relativePathname);
                }
            });
        }
        if (includeOptionsButton) {
            const optionsButton = this.createOptionsButton();
            let savedContextMenu;
            let savedPopup;
            optionsButton.addEventListener('click', () => {
                const contextMenu = (savedContextMenu
                    || this.buildContextMenu(data));
                savedContextMenu = contextMenu;
                if (savedPopup && savedPopup.isOpen) {
                    savedPopup.close();
                } else {
                    const popup = new Popup(
                        optionsButton,
                        contextMenu,
                        item,
                        'RT'
                    );
                    savedPopup = popup;
                }
            });
            item.append(optionsButton);
        }
        return item;
    },
    /**
     * Checks if favorite exists
     * @param {string} place - Area where favorite loads into (side or main)
     * @param {string} path - Path to file
     * @returns
     */
    favoriteExists(place, path) {
        const projectFavorites = this.fetchProjectFavorites();
        for (const [index, item] of Object.entries(projectFavorites)) {
            if (item.place === place && item.path === path) {
                return index;
            }
        }
        return false;
    },
    /**
     * Adds item to favorites menu bar
     * @param {string} title - Favorite item title
     * @param {string} place - Area where bookmark loads into (side or main)
     * @param {string} path - Path to file
     * @param {object} data - File metadata
     * @returns
     */
    addToFavorites(title, place, path, data) {
        if (this.favoriteExists(place, path) !== false) {
            shortNotifications.send(`Favorite already exists`);
            return null;
        }
        const projectFavorites = this.fetchProjectFavorites();
        let maxIndex = 0;
        for (const index of Object.keys(projectFavorites)) {
            if (index > maxIndex) {
                maxIndex = Number(index);
            }
        }
        const index = (maxIndex + 1);
        projectFavorites[index] = {
            title, place, path, data
        };
        if (!this.saveProjectFavorites(projectFavorites)) {
            return false;
        }
        this.addFavoriteButton(title, place, path, index, data);
        shortNotifications.send(`Favorite added`);
        return true;
    },
    /**
     * Fetches favorites for all projects
     * @returns {object} - Object where first level element keys are project
     *     names and values represent an object of favorite list
     */
    fetchAllFavorites() {
        const favoritesStore = localStorage.getItem(
            this.favoritesStoreIndexName
        );
        if (favoritesStore === null) {
            return Object.create(null);
        }
        return JSON.parse(favoritesStore);
    },
    /**
     * Fetches favorites for currently loaded project
     * @returns {object} - Project favorite list
     */
    fetchProjectFavorites() {
        const favoritesObj = this.fetchAllFavorites();
        if (!Object.hasOwn(favoritesObj, this.loadedProjectName)) {
            return Object.create(null);
        }
        return favoritesObj[this.loadedProjectName];
    },
    /**
     * Writes the full favorites payload to local storage
     * @param {object} favorites - Favorites payload
     * @returns {boolean} - Boolean status
     */
    saveAllFavorites(favorites) {
        try {
            localStorage.setItem(
                this.favoritesStoreIndexName,
                JSON.stringify(favorites)
            );
        } catch (error) {
            raiseDialogMessage(`Could not save favorites: ${error.message}`);
            return false;
        }
        return true;
    },
    /**
     * Saves favorite data for the currently loaded project
     * @param {object} projectFavorites - Favorite data
     * @returns {boolean} - Boolean status
     */
    saveProjectFavorites(projectFavorites) {
        const favorites = this.fetchAllFavorites();
        favorites[this.loadedProjectName] = projectFavorites;
        return this.saveAllFavorites(favorites);
    },
    /**
     * Enables a button to load content into side or main panel by its intrinsic
     *     query
     * @param {HTMLButtonElement} button - Button with "data-query" attribute
     */
    bindQueryButton(button) {
        button.addEventListener('click', () => {
            const searchParams = new URLSearchParams(button.dataset.query);
            if (searchParams.has('side')) {
                this.loadIntoSidePanel(searchParams.get('side'), true);
            }
            if (searchParams.has('main')) {
                this.loadIntoMainPanel(searchParams.get('main'), true);
            }
        });
    },
    /**
     * Builds a basic favorite button
     * @param {string} title - Text title
     * @param {string} place - Area where favorite loads into (side or main)
     * @param {string} path - Path to file
     * @returns {HTMLButtonElement}
     */
    createFavoriteButton(title, place, path) {
        return createElement('button', {
            text: title,
            title,
            attrs: {
                'data-query': `${place}=${path}`,
            }
        });
    },
    /**
     * Puts favorite button into the favorites menu bar
     * @param {string} title - Text title
     * @param {string} place - Area where favorite loads into (side or main)
     * @param {string} path - Path to file
     * @param {number} index - Storage index
     * @param {object} data - File metadata
     */
    addFavoriteButton(title, place, path, index, data) {
        let favMenu = this.toolbar.querySelector('.fav-menu');
        if (!favMenu) {
            favMenu = this.buildFavoritesMenu();
            const catMenu = this.toolbar.querySelector('.cat-menu');
            catMenu.insertAdjacentElement('afterend', favMenu);
        }
        const button = this.createFavoriteButton(title, place, path);
        const listItem = createElement('li', {
            classes: ['has-opt', 'type-' + data.type],
            attrs: {
                'data-storage-id': index,
            }
        });
        const removeFavoriteButton = createElement('button', {
            text: "Remove Favorite",
        });
        removeFavoriteButton.addEventListener('click', () => {
            this.removeFavorite(index);
        });
        const optionsButton = this.createOptionsButtonFor(() => {
            const contextMenu = this.buildContextMenu(data);
            contextMenu.getElementsByTagName('ul')[0].append(
                createElement('li').appendChild(
                    removeFavoriteButton
                ).parentElement
            );
            return contextMenu;
        }, listItem);
        listItem.append(button, optionsButton);
        this.bindQueryButton(button);
        const favoriteMenuList = favMenu.querySelector(':scope > ul');
        favoriteMenuList.append(listItem);
    },
    /**
     * Builds favorites menu bar
     * @returns {HTMLMenuElement}
     */
    buildFavoritesMenu() {
        const menu = createElement('menu', {
            classes: ['fav-menu']
        });
        const heading = createElement('button', {
            classes: ['h'],
            text: "Favorites"
        });
        heading.addEventListener('click', () => {
            menu.classList.toggle('open');
        });
        const list = createElement('ul');
        menu.append(heading, list);
        return menu;
    },
    /**
     * Loads all favorites into the favorites menu bar for the currently loaded
     * project
     */
    loadProjectFavorites() {
        const favorites = this.fetchProjectFavorites();
        if (!Object.keys(favorites).length) {
            return null;
        }
        for (const [index, item] of Object.entries(favorites)) {
            this.addFavoriteButton(
                item.title, item.place, item.path, index, item.data
            );
        }
    },
    /**
     * Deletes a single favorite from currently loaded project
     * @param {number} index - Index number of the favorite to delete
     * @returns {boolean} - Boolean status
     */
    removeFavorite(index) {
        const favorites = this.fetchAllFavorites();
        if (!Object.hasOwn(favorites, this.loadedProjectName)) {
            return null;
        }
        const projectFavorites = favorites[this.loadedProjectName];
        if (!Object.hasOwn(projectFavorites, index)) {
            return null;
        }
        delete projectFavorites[index];
        let isEmpty = false;
        // No more favorites left in this project.
        if (!Object.keys(projectFavorites).length) {
            isEmpty = true;
            delete favorites[this.loadedProjectName];
        }
        if (Object.keys(favorites).length) {
            if (!this.saveAllFavorites(favorites)) {
                return false;
            }
        // Favorites object is now totally empty.
        } else {
            this.truncateAllFavorites();
        }
        let queryStr = (!isEmpty)
            ? `.fav-menu li[data-storage-id="${index}"]`
            : `.fav-menu`;
        const elemToRemove = this.toolbar.querySelector(queryStr);
        if (elemToRemove) {
            elemToRemove.remove();
        }
        return true;
    },
    /**
     * Deletes all favorites from local storage
     */
    truncateAllFavorites() {
        localStorage.removeItem(this.favoritesStoreIndexName);
    },
    /**
     * Builds option button
     * @param {closure|HTMLElement} elem - Either a closure that returns an
     *     element or a raw element that will be populated into popup
     * @param {HTMLElement} relElem - Element that should be considered to be
     *     relative to popup
     * @returns
     */
    createOptionsButtonFor(elem, relElem) {
        const button = this.createOptionsButton();
        let popupCopy;
        button.setAttribute('aria-haspopup', 'menu');
        button.addEventListener('click', () => {
            if (popupCopy && popupCopy.isOpen) {
                popupCopy.close();
            } else {
                popupCopy = new Popup(
                    button,
                    (( typeof elem === 'function' )
                        ? elem()
                        : elem),
                    relElem || button
                );
            }
        });
        return button;
    }
};

/**
 * Initial screen to connect to API
 * @type {object}
 */
const welcomeScreen = {
    name: 'welcome',
    menuItems: [],
    connectDataStoreIndexName: 'stonetable_connect_data',
    /**
     * Runs all tasks required to load the screen
     */
    async load() {
        const [screenContainer, screenInner] = super.buildWrappers();
        const templateFragment = this.extractFromTemplate();
        screenInner.append(templateFragment);
        const urlInputElem = screenInner.querySelector('[type=url]');
        produceEmptyControllerButton(urlInputElem);
        bindInputToField(urlInputElem);
        if (urlInputElem.value === '') {
            urlInputElem.value = this.buildGetEndpointsUrl().toString();
            urlInputElem.dispatchEvent(new Event('input', {
                bubbles: true,
                cancelable: false
            }));
        }
        let queryStr = '.cont-btn';
        const submitButtonElem = screenInner.querySelector(queryStr);
        submitButtonElem.disabled = 'true';
        submitButtonElem.addEventListener('click', () => {
            submitButtonElem.disabled = 'true';
            submitButtonElem.classList.add('wtng');
            urlInputElem.disabled = 'true';
            const verifyUrl = this.verifyConnectEndpointUrl(urlInputElem.value);
            verifyUrl.then(data => {
                if (data !== false) {
                    this.storeConnectData(data);
                    connectData = data;
                    isLocalServer = data.isLocalServer;
                    landingScreen.load();
                }
            }).catch(error => {
                raiseDialogMessage(
                    `Could not verify connect endpoint url: ${error.message}`
                );
            }).finally(() => {
                submitButtonElem.removeAttribute('disabled');
                submitButtonElem.classList.remove('wtng');
                urlInputElem.removeAttribute('disabled');
            });
        });
        const checkInput = () => {
            if (urlInputElem.validity.valid) {
                submitButtonElem.removeAttribute('disabled');
            } else {
                submitButtonElem.disabled = 'true';
            }
        };
        urlInputElem.addEventListener('input', () => {
            checkInput();
        });
        if (urlInputElem.value !== '') {
            checkInput();
        }
        if (currentLoadedScreen) {
            currentLoadedScreen.close();
        }
        super.wrapperToBody(screenContainer);
        if (urlInputElem.value !== '') {
            const valueLength = urlInputElem.value.length;
            urlInputElem.setSelectionRange(valueLength, valueLength);
        }
        // After `setSelectionRange()`.
        urlInputElem.focus();
        super.finishLoading();
    },
    /**
     * Checks if given URL is an endpoint which can connect to API
     * @param {string|URL} url - URL to verify
     * @returns {object} - Connect data
     */
    async verifyConnectEndpointUrl(url) {
        return await apiRequest(
            url,
            this.provideAbortController()
        ).then(payload => {
            if (payload === false) {
                return false;
            }
            const data = payload.data;
            if (!this.validateConnectData(data)) {
                throw new Error(`Incomplete payload data`);
            }
            return data;
        });
    },
    /**
     * Validates connect data
     * @param {object} data - Connect data
     * @returns {boolean} - Boolean state
     */
    validateConnectData(data) {
        return (
            ('directoryListing' in data)
            && ('describeFile' in data)
            && ('projectsListing' in data)
            && ('searchDirectory' in data)
            && ('createPlaygroundFile' in data)
            && ('createDemoFile' in data)
            && ('fileHandler' in data)
            && ('unitTests' in data)
            && ('isLocalServer' in data)
        );
    },
    /**
     * Saves connect data into local storage
     * @param {object} data - Connect data
     * @returns {boolean}
     */
    storeConnectData(data) {
        if (!this.validateConnectData(data)) {
            throw new Error(`Connect data is invalid`);
        }
        localStorage.setItem(
            this.connectDataStoreIndexName,
            JSON.stringify(data)
        );
        return true;
    },
    /**
     * Checks if connect data is saved in local storage
     * @returns {boolean}
     */
    hasConnectedData() {
        const key = this.connectDataStoreIndexName;
        return localStorage.getItem(key) !== null;
    },
    /**
     * Fetches connect data from local storage
     * @returns {object} - Connect data
     */
    getConnectData() {
        const dataStr
            = localStorage.getItem(this.connectDataStoreIndexName);
        return JSON.parse(dataStr);
    },
    /**
     * Disconnects from API and loads this welcome screen again
     */
    reconnect() {
        localStorage.removeItem(this.connectDataStoreIndexName);
        this.load();
    },
    /**
     * Attempts to generate a working URL to connect
     * @returns {URL}
     */
    buildGetEndpointsUrl() {
        let pathname = location.pathname;
        if (pathname.endsWith('index.html')) {
            pathname = pathname.substring(0, pathname.length - 10);
        }
        if (pathname.endsWith('/')) {
            pathname = pathname.substring(0, pathname.length - 1);
        }
        const lastSeparator = pathname.lastIndexOf('/');
        if (lastSeparator !== -1) {
            pathname = pathname.substring(0, lastSeparator);
        }
        pathname += '/api/get-endpoints.php';
        const url = new URL(document.location);
        for (const key of url.searchParams.keys()) {
            url.searchParams.delete(key);
        }
        url.pathname = pathname;
        return url;
    }
};

Object.setPrototypeOf(welcomeScreen, genericScreen);
Object.setPrototypeOf(landingScreen, genericScreen);
Object.setPrototypeOf(messageScreen, genericScreen);
Object.setPrototypeOf(managerScreen, genericScreen);

/**
 * Handler for short notifications
 * @type {object}
 */
const shortNotifications = {
    defaultTimeout: 3000,
    init() {
        const container = createElement('div', {
            id: 'short-notifications',
            classes: ['shrt-notifs'],
        });
        this.container = document.body.appendChild(container);
    },
    send(text, timeout = this.defaultTimeout) {
        new ShortNotification(text, timeout);
    },
};

/**
 * Instantiates a new short notification
 * @param {string} text Message text
 * @param {number} timeout Timeout before message is hidden/closed
 */
function ShortNotification(text, timeout) {
    const container = this.createElement();
    container.innerText = text;
    this.container = shortNotifications.container.appendChild(
        container
    );
    this.timeoutHandlerId = setTimeout(() => {
        this.close();
    }, timeout);
    this.container.addEventListener('click', () => {
        this.close();
        clearTimeout(this.timeoutHandlerId);
    });
}

Object.assign(ShortNotification.prototype, {
    createElement() {
        return createElement('div', {
            classes: ['shrt-notif'],
        });
    },
    close() {
        this.container.remove();
    }
});

function factoryParams(params, synthetic = false) {
    if (!welcomeScreen.hasConnectedData()) {
        welcomeScreen.load(params, synthetic);
    } else if (!params || !Object.hasOwn(params, 'project')) {
        landingScreen.load(params, synthetic);
    } else {
        if (!connectData) {
            connectData = welcomeScreen.getConnectData();
        }
        managerScreen.load(params, synthetic);
    }
}

window.addEventListener('popstate', e => {
    factoryParams(e.state, true);
    console.log(`popstate`, e.state);
});

const init = () => {
    const searchParams = new URLSearchParams(location.search);
    const primaryStateObject = Object.fromEntries(searchParams.entries());
    factoryParams(primaryStateObject);
    shortNotifications.init();
}

if (document.readyState === 'interactive') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
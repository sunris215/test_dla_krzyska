"use strict";

// import { equalModuleElementsHeight, accordion } from './elementor-helpers';

const Gravity = {
  /**
   * @param func
   * @param wait
   * @param immediate
   * @returns {function(...[*]=)}
   */
  debounce(func, wait, immediate) {
    var timeout;
    return function () {
      var context = this,
        args = arguments;
      var later = function () {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };
      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(context, args);
    };
  },

  /**
   * @returns {{value: string, key: string}[]|*[]}
   */
  getCookies() {
    if (document.cookie) {
      return document.cookie.split("; ").map((cookie) => {
        const parts = cookie.split("=");
        return { key: parts[0], value: parts[1] };
      });
    } else {
      return [];
    }
  },

  /**
   * @param key
   * @returns {null|*|string}
   */
  getCookie(key) {
    if (!key) {
      return;
    }
    try {
      return this.getCookies()
        .find((cookie) => cookie.startsWith(`${key}=`))
        .split("=")[1];
    } catch {
      return null;
    }
  },

  /**
   * @param {string} key
   * @param {string} value
   * @param {{}} attributes for possible attributes see https://developer.mozilla.org/en-US/docs/Web/API/Document/cookie#write_a_new_cookie
   */
  setCookie(key, value, attributes = {}) {
    if (!key || value == undefined) {
      return;
    }
    let cookie = `${key}=${value}`;
    for (const [attributeKey, attributeValue] of Object.entries(attributes)) {
      cookie += `; ${attributeKey}=${attributeValue}`;
    }
    document.cookie = cookie;
  },

  /**
   * @param {Array<{key: string, value: string, attributes?: {}}>} cookies an array of objects with `key` and `value` properties. `attributes` prop is optional
   */
  setCookies(cookies) {
    if (!Array.isArray(cookies)) {
      return;
    }
    cookies.forEach(({ key, value, attributes = {} }) => {
      this.setCookie(key, value, attributes);
    });
  },

  /**
   * @param {string} key
   * @param {string} path optional. Needed to delete the right cookie when the cookie's path is different than default
   */
  deleteCookie(key, path = null) {
    if (!key) {
      return;
    }
    const attributes = { expires: "Thu, 01 Jan 1970 00:00:00 UTC" };
    if (typeof path === "string") {
      attributes.path = path;
    }
    this.setCookie(key, "", attributes);
  },

  /**
   * @param {Array<{key: string, path?: string}>|Array<string>} cookies array of items which are either objects with `key` and optional `path` properties or strings which will be treated as cookie's key
   */
  deleteCookies(cookies) {
    if (!Array.isArray(cookies)) {
      return;
    }
    cookies.forEach((cookie) => {
      if (typeof cookie === "string") {
        this.deleteCookie(cookie);
      } else if (typeof cookie === "object") {
        this.deleteCookie(cookie.key, cookie.path);
      }
    });
  },

  /**
   * @param {string} key
   * @returns {string|null|undefined} item's value or `null` if doesn't exist or `undefined` if `key` isn't provided
   */
  getLocalStorageItem(key) {
    if (!key) {
      return;
    }
    return window.localStorage.getItem(key);
  },

  /**
   * @returns {Array<{key: string, value: string}>|Array<never>} array of objects with `key` and `value` properties or empty array
   */
  getLocalStorageItems() {
    let items = [];
    for (const [key, value] of Object.entries(window.localStorage)) {
      items.push({ key, value });
    }
    return items;
  },

  /**
   * @param {string} key
   * @param {string} value
   */
  setLocalStorageItem(key, value) {
    if (!key || value == undefined) {
      return;
    }
    window.localStorage.setItem(key, value);
  },

  /**
   * @param {Array<{key: string, value: string}>} items an array of objects with `key` and `value` properties
   */
  setLocalStorageItems(items) {
    if (!Array.isArray(items)) {
      return;
    }
    items.forEach(({ key, value }) => {
      this.setLocalStorageItem(key, value);
    });
  },

  /**
   * @param {string} key
   */
  deleteLocalStorageItem(key) {
    if (!key) {
      return;
    }
    window.localStorage.removeItem(key);
  },

  /**
   * @param {Array<string>} keys an array of strings
   */
  deleteLocalStorageItems(keys) {
    if (!Array.isArray(keys)) {
      return;
    }
    keys.forEach((key) => {
      this.deleteLocalStorageItem(key);
    });
  },

  /**
   * @param {"set"|"delete"} type what do we want to do with the url parameters
   * @param {Array<{key: string, value: string}>|Array<string>} data for type: `set` data should be an array of objects with `key` and `value`; for type: `delete` it should an array of strings
   * @returns {string} updated url string
   */
  getUpdatedUrl(type, data) {
    const urlParams = new URLSearchParams(window.location.search);
    switch (type) {
      case "set":
        data.forEach((item) => {
          urlParams.set(item.key, item.value);
        });
        break;
      case "delete":
        data.forEach((key) => {
          urlParams.delete(key);
        });
        break;
      default:
        break;
    }
    return `?${urlParams.toString()}${window.location.hash}`;
  },

  /**
   * @param {string} url
   * @param {boolean} addHistoryEntry
   */
  updateHistory(url, addHistoryEntry) {
    if (addHistoryEntry) {
      window.history.pushState(url, "", url);
    } else {
      window.history.replaceState(url, "", url);
    }
  },

  /**
   * @param {string} key
   * @returns {string|null|undefined} parameter's value or `null` if such parameter doesn't exist or `undefined` if `key` isn't provided
   */
  getParameter(key) {
    if (!key) {
      return;
    }
    return new URLSearchParams(window.location.search).get(key);
  },

  /**
   * @returns {Array<{key: string, value: string}>|Array<never>} array of objects with `key` and `value` properties or empty array
   */
  getParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const params = [];
    for (const [key, value] of urlParams.entries()) {
      params.push({ key, value });
    }
    return params;
  },

  /**
   * @param {Array<{key: string, value: string}>} parameters an array of objects with `key` and `value` properties
   * @param {boolean} addHistoryEntry create new history entry (user can go back and forward in history). Default `false`
   */
  setParameters(parameters, addHistoryEntry = false) {
    if (!Array.isArray(parameters)) {
      return;
    }
    const url = this.getUpdatedUrl("set", parameters);
    this.updateHistory(url, addHistoryEntry);
  },

  /**
   * @param {string} key
   * @param {string} value
   * @param {boolean} addHistoryEntry create new history entry (user can go back and forward in history). Default `false`
   */
  setParameter(key, value, addHistoryEntry = false) {
    if (!key || value == undefined) {
      return;
    }
    this.setParameters([{ key, value }], addHistoryEntry);
  },

  /**
   * @param {Array<string>} keys an array of strings
   * @param {boolean} addHistoryEntry create new history entry (user can go back and forward in history). Default `false`
   */
  deleteParameters(keys, addHistoryEntry = false) {
    if (!Array.isArray(keys)) {
      return;
    }
    const url = this.getUpdatedUrl("delete", keys);
    this.updateHistory(url, addHistoryEntry);
  },

  /**
   * @param {string} key
   * @param {boolean} addHistoryEntry create new history entry (user can go back and forward in history). Default `false`
   */
  deleteParameter(key, addHistoryEntry = false) {
    if (!key) {
      return;
    }
    this.deleteParameters([key], addHistoryEntry);
  },

  /**
   * @param {boolean} addHistoryEntry create new history entry (user can go back and forward in history). Default `false`
   */
  clearUrl(addHistoryEntry = false) {
    this.updateHistory(window.location.pathname, addHistoryEntry);
  },

  /**
   * @param {string} selector proper css selector
   * @param {{}} options for options see https://developer.mozilla.org/en-US/docs/Web/API/Element/scrollIntoView#parameters
   */
  scrollToSelector(selector, options = {}) {
    if (typeof selector == undefined) {
      return;
    }
    const el = document.querySelector(selector);
    if (el) {
      el.scrollIntoView(options);
    }
  },

  // accordion,

  // equalModuleElementsHeight
};

export default Gravity;
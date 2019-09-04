/**
 * Session
 * A utility for fetching and caching the current session token from Drupal.
 * This token is then used as the `X-CSRF-Token` header when making write api requests.
 */
const session = {
  token: null,

  /**
   * getToken()
   * Retrieves the current session token. If one is not saved already,
   *   it will fetch it from the server.
   * @returns Promise A promise which resolves to the current session token.
   */
  getToken() {
    if (this.token) {
      return Promise.resolve(this.token);
    }

    return fetch('/session/token', {
      credentials: 'same-origin',
    })
      .then(res => res.text())
      .then(token => token)
      .catch((e) => {
        console.log(e);
      });
  },

  /**
   * setToken()
   * Sets the current token for use.
   * @param string A promise which resolves to the current session token.
   */
  setToken(token) {
    this.token = token;
  },
};

export default session;

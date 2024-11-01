// @ts-check

var script_vars; // SUPPLIED BY PHP BACKEND

const syscoinSettings = new class {
	constructor() {
		this.sections.wholePage.fill(script_vars.settings);

		this.sections.account.fill(script_vars.settings);
		this.sections.ai.fill(script_vars.settings);
		this.sections.misc.fill(script_vars.settings);

		this.sections.account.setLoading(false);
		this.sections.ai.setLoading(false);
		this.sections.misc.setLoading(false);

		utils.finishLoading();
	}

	/**
	 * The visually-separated sections of the settings page.
	 */
	sections = {
		/**
		 * Elements and methods related to the whole page.
		 */
		wholePage: new class {
			elements = {
				forLoggedIn: jQuery('#syscoin-settings-for-logged-in-users'),
				forLoggedOut: jQuery('#syscoin-settings-for-logged-out-users'),
			};

			fill(settings) {
				if (settings.user?.['logged_in']) {
					this.elements.forLoggedIn.show();
					this.elements.forLoggedOut.hide();
				}
				else {
					this.elements.forLoggedIn.hide();
					this.elements.forLoggedOut.show();
				}
			}
		},

		/**
		 * The section where account information is shown.
		 */
		account: new class {
			constructor() {
				this.elements.actions.logIn.on('click', async (event) => {
					event.preventDefault();

					const input = this.getLoginData();

					this.requests.logIn(input.username, input.password);
				});

				this.elements.actions.logOut.on('click', async (event) => {
					event.preventDefault();

					this.requests.logOut();
				});
			}

			elements = {
				forms: {
					login: jQuery('#syscoin-login-form'),
					logout: jQuery('#syscoin-logout-form'),
				},
				messages: {
					forPremium: jQuery('#syscoin-message-for-premium'),
					forNonPremium: jQuery('#syscoin-message-for-non-premium'),
				},
				inputs: {
					username: jQuery('#syscoin-login-username'),
					password: jQuery('#syscoin-login-password'),
				},
				actions: {
					logIn: jQuery('#syscoin-login-button'),
					logOut: jQuery('#syscoin-logout-button'),
				},
				spinners: {
					loggingIn: jQuery('#syscoin-login-spinner'),
					logginOut: jQuery('#syscoin-logout-spinner'),
				}
			};

			requests = {
				logIn: async (username, password) => {
					if (!username || !password) {
						alert('Please enter username and password');
						return;
					}

					this.setLoading(true);

					utils.ajax({
						nonce: script_vars.nonce,
						action: 'login',
						data: {
							'username': username,
							'password': password
						},
						success: (response) => {
							if (response.success) {
								console.log('[Syscoin] Successfully logged in. Reloading page...');
								location.reload();
							}
							else {
								this.setLoading(false);
								console.error('[Syscoin] Login error:', response);
								alert(response.message);
							}
						},
						error: (xhr, status, error) => {
							this.setLoading(false);
							console.error('[Syscoin] Login ajax error:', xhr.statusText);
							alert('Could not log in at this time. Please contact support.');
						}
					});
				},

				logOut: async () => {
					this.setLoading(true);

					await utils.ajax({
						nonce: script_vars.nonce,
						action: 'logout',
						success: (response) => {
							if (response.success) {
								console.log('[Syscoin] Successfully logged out. Reloading page...');
								location.reload();
							}
							else {
								this.setLoading(false);
								console.error('[Syscoin] Logout error:', JSON.stringify(response));
								alert('Could not log out. Please try again.');
							}
						},
						error: (xhr, status, error) => {
							this.setLoading(false);
							console.error('[Syscoin] Logout ajax error:', xhr.statusText);
						}
					});
				}
			};

			fill(settings) {
				this.setPremiumMessage(settings?.user);
				this.setAnonymousUserSection(settings?.user);
			}

			setLoading(loading) {
				if (loading === true) {
					this.elements.spinners.loggingIn.show();
					this.elements.inputs.username.attr('disabled', 'disabled');
					this.elements.inputs.password.attr('disabled', 'disabled');
					this.elements.actions.logIn.attr('disabled', 'disabled');

					this.elements.spinners.logginOut.show();
					this.elements.actions.logOut.attr('disabled', 'disabled');
				}
				else {
					this.elements.spinners.loggingIn.hide();
					this.elements.inputs.username.removeAttr('disabled');
					this.elements.inputs.password.removeAttr('disabled');
					this.elements.actions.logIn.removeAttr('disabled');

					this.elements.spinners.logginOut.hide();
					this.elements.actions.logOut.removeAttr('disabled');
				}
			}

			/**
				* Retrieves the login data from the DOM.
				*/
			getLoginData() {
				return {
					username: this.elements.inputs.username.val().toString(),
					password: this.elements.inputs.password.val().toString()
				};
			}

			setPremiumMessage(user) {
				if (user?.['premium']) {
					this.elements.messages.forPremium.show();
					this.elements.messages.forNonPremium.hide();
				}
				else {
					this.elements.messages.forPremium.hide();
					this.elements.messages.forNonPremium.show();
				}
			}

			setAnonymousUserSection(user) {
				if (user?.['username'] === 'anonymous') {
					jQuery('#syscoin-settings-for-logged-out-users').show(); // TODO: melhorar essa query
					this.elements.forms.login.show();
					this.elements.forms.logout.hide();
				}
			}
		},

		/**
		 * The section where AI features options are shown.
		 */
		ai: new class {
			constructor() {
				this.elements.actions.save.on('click', async (event) => {
					event.preventDefault();

					const input = this.elements.inputs.key.val().toString();

					this.requests.saveOpenAiKey(input);
				});

				this.elements.actions.remove.on('click', async (event) => {
					event.preventDefault();

					this.requests.removeOpenAiKey();
				});
			};

			elements = {
				versions: {
					forKeyNotSaved: jQuery('#syscoin-custom-openai-token-for-not-saved'),
					forKeySaved: jQuery('#syscoin-custom-openai-token-for-saved'),
				},
				inputs: {
					key: jQuery('#syscoin-input-custom-openai-token'),
				},
				actions: {
					save: jQuery('#syscoin-save-custom-openai-token'),
					remove: jQuery('#syscoin-remove-custom-openai-token'),
				},
				spinners: {
					saving: jQuery('#syscoin-save-openai-key-spinner-save'),
					removing: jQuery('#syscoin-save-openai-key-spinner-remove'),
				}
			};

			requests = {
				saveOpenAiKey: async (key) => {
					if (!key) {
						alert('Please enter a key.');
						return;
					}

					this.setLoading(true);

					await utils.ajax({
						nonce: script_vars.nonce,
						action: 'saveOpenAiKey',
						data: { key: key },
						success: (response) => {
							if (response.success) {
								console.log('[Syscoin] Successfully saved OpenAI key.');
								location.reload();
							}
							else {
								this.setLoading(false);
								console.error('[Syscoin] OpenAI key save error:', response);
								alert(response.message);
							}
						},
						error: (xhr, status, error) => {
							this.setLoading(false);
							console.error('[Syscoin] OpenAI key save ajax error:', xhr.statusText);
						}
					});
				},

				removeOpenAiKey: async () => {
					this.setLoading(true);

					await utils.ajax({
						nonce: script_vars.nonce,
						action: 'removeOpenAiKey',
						success: (response) => {
							if (response.success) {
								console.log('[Syscoin] Successfully removed OpenAI key.');
								location.reload();
							}
							else {
								this.setLoading(false);
								console.error('[Syscoin] OpenAI key removal error:', response);
								alert(response.message);
							}
						},
						error: (xhr, status, error) => {
							this.setLoading(false);
							console.error('[Syscoin] OpenAI key removal ajax error:', xhr.statusText);
						}
					});
				}
			};

			fill(settings) {
				if (settings?.user?.openai_key_preview) {
					this.elements.versions.forKeySaved.show();
					this.elements.versions.forKeyNotSaved.hide();
				}
				else {
					this.elements.versions.forKeySaved.hide();
					this.elements.versions.forKeyNotSaved.show();
				}
			}

			setLoading(loading) {
				if (loading === true) {
					this.elements.spinners.saving.show();
					this.elements.spinners.removing.show();
				}
				else {
					this.elements.spinners.saving.hide();
					this.elements.spinners.removing.hide();
				}

				this.toggleInputs(loading);
			}

			toggleInputs(disable) {
				if (disable === true) {
					this.elements.inputs.key.attr('disabled', 'disabled');
					this.elements.actions.save.attr('disabled', 'disabled');
					this.elements.actions.remove.attr('disabled', 'disabled');
				}
				else {
					this.elements.inputs.key.removeAttr('disabled');
					this.elements.actions.save.removeAttr('disabled');
					this.elements.actions.remove.removeAttr('disabled');
				}
			}
		},

		/**
		 * The section where other options are shown.
		 */
		misc: new class {
			constructor() {
				this.elements.inputs.showFooter.on('click', async (event) => {
					event.preventDefault();

					this.requests.saveFooterSettings({
						enable: this.elements.inputs.showFooter.prop('checked'),
					});
				});
			}

			elements = {
				inputs: {
					showFooter: jQuery('#syscoin-footer-enable')
				},
				spinners: {
					savingFooter: jQuery('#syscoin-misc-footer-spinner')
				}
			};

			requests = {
				saveFooterSettings: async (settings) => {
					this.setLoading(true);

					await utils.ajax({
						nonce: script_vars.nonce,
						action: 'updateFooterSettings',
						data: settings,
						success: (response) => {
							this.setLoading(false);

							if (response.success) {
								console.log('[Syscoin] Successfully updated footer settings.');

								const data = {
									show_agency_footer: response.state
								};

								syscoinSettings.sections.misc.fill(data);
							}
							else {
								console.error('[Syscoin] Footer settings update error:', response);
								alert(response.message);
							}
						},
						error: (xhr, status, error) => {
							this.setLoading(false);

							console.error('[Syscoin] Footer settings update ajax error:', xhr.statusText);
						}
					});
				}
			};

			fill(settings) {
				this.elements.inputs.showFooter.prop('checked', settings?.['show_agency_footer']);
			}

			setLoading(loading) {
				if (loading === true) {
					this.elements.spinners.savingFooter.show();
					this.elements.inputs.showFooter.attr('disabled', 'disabled');
				}
				else {
					this.elements.spinners.savingFooter.show().hide();
					this.elements.inputs.showFooter.removeAttr('disabled');
				}
			}
		}
	};
};

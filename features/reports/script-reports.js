// @ts-check

/**
	* Settings for the reports, in the format expected by the WordPress backend.
	* 
	* @typedef {{
		*   schedules: {
			* 		daily: {
			* 			enable: boolean;
			* 		};
			* 		weekly: {
			* 			enable: boolean;
			* 			weekday: string;
			* 		};
			* 		monthly: {
			* 			enable: boolean;
			* 		};
			*		};
			* 	mobiles: {
			* 		mobile1: string;
			* 		mobile2: string;
			* 		mobile3: string;
			* 	};
			* 	time: string;
			*		webhook?: string;
			*		topics?: string[];
			* }} ReportSettings 
		 */

var script_vars; // SUPPLIED BY PHP BACKEND

const syscoinReports = new class {
	constructor() {
		this.elements.inputs.all.on('change input', async (event) => {
			this.toggleSave();
		});

		this.elements.actions.save.on('click', async (event) => {
			this.requests.saveReportSettings(this.getDisplayedSettings());
		});

		this.elements.actions.send.on('click', async (event) => {
			this.requests.sendReportNow();
		});

		this.fill(script_vars.settings?.report_settings);

		this.setSaving(false);
		this.setSending(false);

		utils.finishLoading();

	}

	elements = {
		all: jQuery('[id^="syscoin-reports-"]'),
		inputs: {
			all: jQuery("[id^='syscoin-reports-input-']"),
			dailyEnable: jQuery('#syscoin-reports-input-enable-daily'),
			weeklyEnable: jQuery('#syscoin-reports-input-enable-weekly'),
			weeklyWeekday: jQuery('#syscoin-reports-input-weekly-weekday'),
			monthlyEnable: jQuery('#syscoin-reports-input-enable-monthly'),
			preferredTime: jQuery('#syscoin-reports-input-preferred-time'),
			mobile1: jQuery('#syscoin-reports-input-mobile-1'),
			mobile2: jQuery('#syscoin-reports-input-mobile-2'),
			mobile3: jQuery('#syscoin-reports-input-mobile-3'),
			webhook: jQuery('#syscoin-reports-input-webhook'),
			includeAccessCount: jQuery('#syscoin-reports-input-optional-accesses'),
			includeSalesCount: jQuery('#syscoin-reports-input-optional-sales'),
			includeIncome: jQuery('#syscoin-reports-input-optional-income'),
			includeUtm: jQuery('#syscoin-reports-input-optional-utm'),
			includeMostAccessedPages: jQuery('#syscoin-reports-input-optional-most-accessed-pages'),
			includeMostViewedProds: jQuery('#syscoin-reports-input-optional-most-viewed-prods'),
			includeMostSoldProducts: jQuery('#syscoin-reports-input-optional-most-sold-prods'),
		},
		actions: {
			save: jQuery('#syscoin-reports-action-save'),
			send: jQuery('#syscoin-reports-action-send')
		},
		spinners: {
			saving: jQuery('#syscoin-save-reports-settings-spinner'),
			sending: jQuery('#syscoin-send-reports-settings-spinner'),
		}
	};

	requests = {
		sendReportNow: async () => {
			this.setSending(true);

			await utils.ajax({
				nonce: script_vars.nonce,
				action: 'sendReportNow',
				success: (response) => {
					this.setSending(false);

					if (response.success) {
						console.log('[Syscoin] Successfully sent report request.');
					}
					else {
						console.error('[Syscoin] Report request error:', response);
						alert(response.message);
					}
				},
				error: (xhr, status, error) => {
					this.setSending(false);

					console.error('[Syscoin] Report request ajax error:', xhr.statusText);
				}
			});
		},

		/**
		 * @param { ReportSettings } settings
		 */
		saveReportSettings: async (settings) => {
			this.setSaving(true);

			await utils.ajax({
				nonce: script_vars.nonce,
				action: 'updateReportSettings',
				data: { json: JSON.stringify(settings) },
				success: (response) => {
					this.setSaving(false);

					if (response.success) {
						console.log('[Syscoin] Successfully updated report settings.');
						this.fill(settings);
					}
					else {
						console.error('[Syscoin] Report settings update error:', response);
						alert(response.message);
					}
				},
				error: (xhr, status, error) => {
					this.setSaving(false);

					console.error('[Syscoin] Report settings update ajax error:', xhr.statusText);
				}
			});
		}
	};

	/**
	 * A copy of the settings that are currently saved in the WordPress backend. This is used to compare with the user's input.
	 * @type {ReportSettings}
	 */
	savedSettings;

	/**
	 * Get the settings being displayed in the front-end.
	 *
	 * @return {ReportSettings} 
	 */
	getDisplayedSettings() {
		/** @type {ReportSettings} */
		return {
			schedules: {
				daily: {
					enable: this.elements.inputs.dailyEnable.prop('checked'),
				},
				weekly: {
					enable: this.elements.inputs.weeklyEnable.prop('checked'),
					weekday: (this.elements.inputs.weeklyWeekday.val() || '').toString(),
				},
				monthly: {
					enable: this.elements.inputs.monthlyEnable.prop('checked')
				},
			},
			mobiles: {
				mobile1: this.elements.inputs.mobile1.val().toString() || null,
				mobile2: this.elements.inputs.mobile2.val().toString() || null,
				mobile3: this.elements.inputs.mobile3.val().toString() || null,
			},
			time: utils.hoursMinutesToGMT(this.elements.inputs.preferredTime.val()),
			webhook: this.elements.inputs.webhook.val().toString() || null,
			topics: this.getTopicsArray(),
		};
	}

	/**
	 * Get the topics options as selected in the DOM.
	 *
	 * @returns {string[]}
	 */
	getTopicsArray() {
		const topics = {
			'access_count': this.elements.inputs.includeAccessCount.prop('checked'),
			'sales_count': this.elements.inputs.includeSalesCount.prop('checked'),
			'income': this.elements.inputs.includeIncome.prop('checked'),
			'utm': this.elements.inputs.includeUtm.prop('checked'),
			'most_acessed_pages': this.elements.inputs.includeMostAccessedPages.prop('checked'),
			'most_viewed_prods': this.elements.inputs.includeMostViewedProds.prop('checked'),
			'most_sold_prods': this.elements.inputs.includeMostSoldProducts.prop('checked'),
		};

		return Object.entries(topics)
			.filter(([key, value]) => value === true)
			.map(([key, value]) => key);
	}

	/**
	 * Fill the reports division with the supplied data.
	 *
	 * @param { ReportSettings } settings
	 */
	fill(settings) {
		if (!settings) return;

		this.savedSettings = settings;

		this.elements.inputs.dailyEnable.prop('checked', settings.schedules.daily.enable);
		this.elements.inputs.weeklyEnable.prop('checked', settings.schedules.weekly.enable);
		this.elements.inputs.monthlyEnable.prop('checked', settings.schedules.monthly.enable);

		this.elements.inputs.weeklyWeekday.val(settings.schedules.weekly.weekday);

		this.elements.inputs.preferredTime.val(utils.hoursMinutesToLocal(settings.time));

		if (!settings.time) {
			this.elements.inputs.preferredTime.val('08:00');
		}

		this.elements.inputs.mobile1.val(settings.mobiles.mobile1);
		this.elements.inputs.mobile2.val(settings.mobiles.mobile2);
		this.elements.inputs.mobile3.val(settings.mobiles.mobile3);

		this.elements.inputs.webhook.val(settings.webhook);

		if (settings.topics) {
			this.elements.inputs.includeAccessCount.prop('checked', settings.topics.includes('access_count'));
			this.elements.inputs.includeSalesCount.prop('checked', settings.topics.includes('sales_count'));
			this.elements.inputs.includeIncome.prop('checked', settings.topics.includes('income'));
			this.elements.inputs.includeUtm.prop('checked', settings.topics.includes('utm'));
			this.elements.inputs.includeMostAccessedPages.prop('checked', settings.topics.includes('most_acessed_pages'));
			this.elements.inputs.includeMostViewedProds.prop('checked', settings.topics.includes('most_viewed_prods'));
			this.elements.inputs.includeMostSoldProducts.prop('checked', settings.topics.includes('most_sold_prods'));
		}

		this.elements.actions.save.attr('disabled', 'disabled');

		this.toggleSave();
	}

	setSaving(isSaving) {
		if (isSaving === true) {
			this.elements.spinners.saving.show();
			this.elements.all.attr('disabled', 'disabled');
		}
		else {
			this.elements.spinners.saving.hide();
			this.elements.all.removeAttr('disabled');
		}

		this.toggleSave();

		if (isSaving === true) {
			this.elements.actions.save.attr('disabled', 'disabled');
			this.elements.actions.send.attr('disabled', 'disabled');
		}
		else {
			this.elements.actions.send.removeAttr('disabled');
		}
	}

	setSending(isSending) {
		if (isSending === true) {
			this.elements.spinners.sending.show();
			this.elements.all.attr('disabled', 'disabled');
			this.elements.actions.send.attr('disabled', 'disabled');
		}
		else {
			this.elements.spinners.sending.hide();
			this.elements.all.removeAttr('disabled');
			this.elements.actions.send.removeAttr('disabled');
		}

		this.toggleSave();

		if (isSending === true) {
			this.elements.actions.send.attr('disabled', 'disabled');
		}
		else {
			this.elements.actions.send.removeAttr('disabled');
		}
	}

	toggleSave() {
		let dailyOk = true, weeklyOk = true, monthlyOk = true, mobilesOk = true;

		const isTimeSelected = this.elements.inputs.preferredTime.val();
		const isWeekdaySelected = this.elements.inputs.weeklyWeekday?.val() || null;
		const noMobileNumbers = !this.elements.inputs.mobile1.val() && !this.elements.inputs.mobile2.val() && !this.elements.inputs.mobile3.val();

		if (this.elements.inputs.dailyEnable.prop('checked') && !isTimeSelected) {
			dailyOk = false;
		}

		if (this.elements.inputs.weeklyEnable.prop('checked') && (!isTimeSelected || !isWeekdaySelected)) {
			weeklyOk = false;
		}

		if (this.elements.inputs.monthlyEnable.prop('checked') && !isTimeSelected) {
			monthlyOk = false;
		}

		if (noMobileNumbers) {
			mobilesOk = false;
		}

		const settingsAreValid = dailyOk && weeklyOk && monthlyOk && mobilesOk;

		const okToSave = settingsAreValid && !utils.deepEqual(this.savedSettings, this.getDisplayedSettings());

		if (okToSave) {
			this.elements.actions.save.removeAttr('disabled');
			this.elements.actions.send.attr('disabled', 'disabled');
		}
		else {
			this.elements.actions.save.attr('disabled', 'disabled');
			this.elements.actions.send.removeAttr('disabled');
		}
	}
};

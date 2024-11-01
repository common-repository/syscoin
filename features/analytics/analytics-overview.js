// @ts-check

var script_vars; // SUPPLIED BY PHP BACKEND

const analyticsService = new class {
	async getIndividualRequestsTable(start, end) {
		await utils.ajax({
			nonce: script_vars.nonce,
			action: 'getRequestsTable',
			data: {
				start: start,
				end: end,
			}, // TODO: add filtering feature
			success: (response) => {
				if (response.success && response.htmlTable) {
					analyticsController.openWindow(response.htmlTable);
				}
				else {
					console.error('[Syscoin] Table window error:', response);
					alert('Unable to open requests table window. (backend error)');
				}
			},
			error: (xhr, status, error) => {
				console.error('[Syscoin] Table window error:', xhr.statusText);
				alert('Unable to open requests table window. (request error)');
			}
		});
	}

	async getDataForPeriod(start, end, granularity, tz) {
		return new Promise((res, rej) => {
			utils.ajax({
				nonce: script_vars.nonce,
				action: 'getPeriodData',
				data: {
					start: start,
					end: end,
					granularity: granularity,
					tz: tz,
				},
				success: (response) => {
					if (response.success && response.data && response.html) {
						res(response);
					}
					else {
						console.error('[Syscoin] Error getting data for time period:', start, end, response);
						alert('Unable to get data for this time period. (backend error)');
					}
				},
				error: (xhr, status, error) => {
					console.error('[Syscoin] XHR error getting data for time period', start, end, xhr.statusText);
					alert('Unable to get data for this time period. (request error)');
				}
			});
		});
	}
};

const analyticsController = new class {
	constructor() {
		this.forceVerticalScrollbar();
		this.setListeners();
		this.updatePeriod(this.elements.inputs.periodSelect.val());
	};

	elements = {
		versions: {
			forLoggedOut: jQuery('#syscoin-analytics-for-logged-out-users'),
			forLoggedIn: jQuery('#syscoin-analytics-for-logged-in-users'),
			forEmptyLogs: jQuery('#syscoin-analytics-for-empty-access-logs'),
		},
		navbar: {
			general: jQuery('#syscoin-analytics-navbar-general'),
			utm: jQuery('#syscoin-analytics-navbar-utm'),
			individual: jQuery('#syscoin-analytics-navbar-individual'),
		},
		inputs: {
			all: jQuery('[id^="syscoin-analytics-period-custom-"]'),
			periodSelect: jQuery('#syscoin-analytics-period-select'),
			periodCustomStart: jQuery('#syscoin-analytics-period-custom-start'),
			periodCustomEnd: jQuery('#syscoin-analytics-period-custom-end'),
		},
		actions: {
			openIndividualRequests: jQuery('#syscoin-open-individual-requests'),
		},
		spots: {
			periodStart: jQuery('#syscoin-analytics-period-start'),
			periodEnd: jQuery('#syscoin-analytics-period-end'),
			generalSummary: jQuery('#syscoin-analytics-general-summary'),
			generalPages: jQuery('#syscoin-analytics-general-pages'),
			utmCampaign: jQuery('#syscoin-analytics-utm-campaign'),
			utmContent: jQuery('#syscoin-analytics-utm-content'),
			utmMedium: jQuery('#syscoin-analytics-utm-medium'),
			utmSource: jQuery('#syscoin-analytics-utm-source'),
			utmTerm: jQuery('#syscoin-analytics-utm-term'),
		},
		spinners: {
			logsSpinner: jQuery('#syscoin-analytics-logs-loading'),
		},
		chartjs: [], // to be filled by the script.
	};

	setListeners() {
		this.elements.inputs.periodSelect.on('change', (event) => {
			const selection = this.elements.inputs.periodSelect.val();

			this.updatePeriod(selection);
		});

		this.elements.actions.openIndividualRequests.on('click', async () => {
			this.elements.spinners.logsSpinner.show();
			const selection = this.elements.inputs.periodSelect.val();
			const period = this.getPeriodDelimiters(selection);

			await analyticsService.getIndividualRequestsTable(period.start, period.end);

			this.elements.spinners.logsSpinner.hide();
		});

		this.elements.spinners.logsSpinner.hide();

		this.setNavbarListeners();

		this.elements.inputs.all.on('change', (event) => {
			const start = this.elements.inputs.periodCustomStart.val();
			const end = this.elements.inputs.periodCustomEnd.val();

			if (start && end) {
				this.elements.inputs.periodSelect.val('custom');
				this.updatePeriod(this.getPeriodDelimiters('custom'));
			}
		});
	}

	forceVerticalScrollbar() {
		const style = document.createElement('style');

		style.innerHTML = `
			html {
				overflow-y: scroll;
			}
		`;

		document.head.appendChild(style);
	}

	getPeriodDelimiters(period) {
		switch (period) {
			// case 'this-day':
			// 	return {
			// 		start: 'today midnight',
			// 		end: 'tomorrow midnight - 1 second',
			// 		defaultGranularity: 'hour'
			// 	};

			case 'last-7days':
				return {
					start: '7 days ago midnight',
					end: 'tomorrow - 1 second',
					defaultGranularity: 'day',
					tz: utils.tz(),
				};

			case 'last-30days':
				return {
					start: '30 days ago midnight',
					end: 'tomorrow - 1 second',
					defaultGranularity: 'day',
					tz: utils.tz(),
				};

			case 'last-90days':
				return {
					start: '90 days ago midnight',
					end: 'tomorrow - 1 second',
					defaultGranularity: 'day',
					tz: utils.tz(),
				};

			case 'last-180days':
				return {
					start: '180 days ago midnight',
					end: 'tomorrow - 1 second',
					defaultGranularity: 'day',
					tz: utils.tz(),
				};

			case 'this-month':
				return {
					start: 'first day of this month',
					end: 'first day of next month - 1 second',
					defaultGranularity: 'day',
					tz: utils.tz(),
				};

			case 'prev-month':
				return {
					start: 'first day of last month',
					end: 'first day of this month - 1 second',
					defaultGranularity: 'day',
					tz: utils.tz(),
				};

			case 'this-year':
				return {
					start: 'first day of January this year',
					end: 'first day of January next year - 1 second',
					defaultGranularity: 'day',
					tz: utils.tz(),
				};

			case 'prev-year':
				return {
					start: 'first day of January last year',
					end: 'first day of January this year - 1 second',
					defaultGranularity: 'day',
					tz: utils.tz(),
				};

			// case 'prev-day':
			// 	return {
			// 		start: 'yesterday midnight',
			// 		end: 'today midnight- 1 second',
			// 		defaultGranularity: 'hour'
			// 	};



			// case 'custom':
			// 	return {
			// 		start: this.elements.inputs.periodCustomStart.val(),
			// 		end: this.elements.inputs.periodCustomEnd.val(),
			// 		defaultGranularity: 'day'
			// 	};

			default:
				console.warn(period);
				throw new Error(`Invalid period selected`);
		}
	}

	setNavbarListeners() {
		for (let [page, element] of Object.entries(this.elements.navbar)) {
			element.on('click', (event) => {
				this.switchTab(page);
			});
		}
	}

	async updatePeriod(period) {
		utils.startLoading();

		period = this.getPeriodDelimiters(period);

		const { data, html } = await analyticsService.getDataForPeriod(period.start, period.end, period.defaultGranularity, period.tz);

		this.fillPage(data, html);

		const start = (new Date(data.date_start)).toLocaleDateString();
		const end = (new Date(data.date_end)).toLocaleDateString();

		this.elements.spots.periodStart.html(start);
		this.elements.spots.periodEnd.html(end);

		utils.finishLoading();
	}

	fillPage(data, html) {
		this.charts.destroyAll();

		this.elements.versions.forLoggedOut.hide();
		this.elements.versions.forLoggedIn.hide();
		this.elements.versions.forEmptyLogs.hide();

		if (!script_vars.logged_in) {
			this.elements.versions.forLoggedOut.show();
			return;
		}

		if (data['is_empty'] === true) {
			this.elements.versions.forEmptyLogs.show();
			return;
		}

		this.elements.versions.forLoggedIn.show();

		if (window.location.hash) {
			this.switchTab(window.location.hash.replace('#', ''));
		}
		else {
			this.switchTab('general');
		}

		this.charts.smallDonut('utmCampaignsPieChart', data.utm_counts.campaign);
		this.charts.smallDonut('utmContentsPieChart', data.utm_counts.content);
		this.charts.smallDonut('utmMediumsPieChart', data.utm_counts.medium);
		this.charts.smallDonut('utmSourcesPieChart', data.utm_counts.source);
		this.charts.smallDonut('utmTermsPieChart', data.utm_counts.term);

		this.charts.line('dailyAccessLineChart', data.day_counts);

		this.charts.pie('pageAccessPieChart', data.page_counts);

		this.elements.spots.generalSummary.html(html.general['summary']);
		this.elements.spots.generalPages.html(html.general['pages']);

		this.elements.spots.utmCampaign.html(html.utm.campaign);
		this.elements.spots.utmContent.html(html.utm.content);
		this.elements.spots.utmMedium.html(html.utm.medium);
		this.elements.spots.utmSource.html(html.utm.source);
		this.elements.spots.utmTerm.html(html.utm.term);
	}

	switchTab(tab) {
		jQuery("[id^='syscoin-analytics-tab-']").hide();
		jQuery('#syscoin-analytics-tab-' + tab).show();
		jQuery("[id^='syscoin-analytics-navbar-']").removeClass('nav-tab-active');
		jQuery('#syscoin-analytics-navbar-' + tab).addClass('nav-tab-active');
	}

	openWindow(content) {
		var popup = globalThis.window.open('', 'TableWindow', 'width=1200,height=800');

		var htmlContent = `
			<!DOCTYPE html>
			<html>
			<head>
				<title>Individual Access Logs</title>
				<style>
					body { font-family: Arial, sans-serif; background: #f0f0f0; font-size: 12px; }
					table { width: 100%; border-collapse: collapse; }
					th, td { padding: 3px; border: 1px solid #ccc; text-align: left; white-space: nowrap; }
					tr:nth-child(odd) { background-color: #f2f2f2; }
					tr:nth-child(even) { background-color: #ffffff; }
				</style>
			</head>
			<body>
				${content}
			</body>
			</html>
    `;

		popup.document.write(htmlContent);
		popup.document.close();
	}


	charts = {
		destroyAll: () => {
			this.elements.chartjs.forEach((chartInstance) => {
				chartInstance.destroy();
			});
		},
		line: (id, data) => {
			data = utils.convertDateKeysToLocale(data);

			const instance = new Chart( // @ts-ignore
				document.getElementById(id).getContext('2d'),
				{
					type: 'line',
					data: { // @ts-ignore
						labels: Object.keys(data),
						datasets: [{
							label: '', // @ts-ignore
							data: Object.values(data),
							borderColor: '#fbb73a',
							pointRadius: 1,
							pointHoverRadius: 10,
							tension: 0.1,
						}]
					},
					options: {
						scales: {// @ts-ignore
							y: {
								beginAtZero: true
							}
						},
						plugins: { // @ts-ignore
							legend: {
								display: false
							}
						}
					}
				}
			);

			this.elements.chartjs.push(instance);
		},
		pie: (id, data) => {
			const instance = new Chart( // @ts-ignore
				document.getElementById(id).getContext('2d'),
				{
					type: 'pie',
					data: { // @ts-ignore
						labels: Object.keys(data),
						datasets: [{ // @ts-ignore
							data: Object.values(data),
							backgroundColor: Object.keys(data).map(label => utils.getHashColor(label)),
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: { // @ts-ignore
							legend: {
								display: false
							}
						}
					}
				}
			);

			this.elements.chartjs.push(instance);
		},
		smallDonut: (id, data) => {
			const instance = new Chart( // @ts-ignore
				document.getElementById(id).getContext('2d'),
				{
					type: 'doughnut',
					data: {
						labels: Object.keys(data), // @ts-ignore
						datasets: [{ // @ts-ignore
							data: Object.values(data),
							backgroundColor: Object.keys(data).map(label => utils.getHashColor(label)),
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								display: false,
							}
						},
					}
				}
			);

			this.elements.chartjs.push(instance);
		},
	};
};


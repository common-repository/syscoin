// @ts-check

var script_vars; // SUPPLIED BY PHP BACKEND

const diagnosticsController = new class {
	constructor() {
		utils.finishLoading();

		this.setListeners();

		const params = new URLSearchParams(window.location.search);

		if (params.has('id')) {
			this.fillReportPage();
		}
		else {
			this.fillHomePage();
		}
	};

	elements = {
		inputs: {
			scheduleScan: jQuery('#syscoin-diagnostics-schedule'),
		},
		actions: {
			requestDiagnosis: jQuery('#syscoin-diagnostics-request-diagnosis'),
			back: jQuery('#syscoin-diagnostics-back'),
		},
		spinners: {
			diagnosing: jQuery('#syscoin-diagnostics-spinner-diagnosing'),
			saving: jQuery('#syscoin-diagnostics-spinner-saving')
		},
		spots: {
			scoreBar: jQuery('#syscoin-diagnostics-score-bar'),
			allListTitles: jQuery('div[id^="syscoin-diagnostics-list-title-id-"]'),
			allListButtons: jQuery('button[id^="syscoin-diagnostics-list-open-id-"]'),
			reportDate: jQuery('#syscoin-diagnostics-report-date'),
		},
		chartjs: [],
	};

	requests = {
		diagnose: async () => {
			this.setDiagnosing(true);

			await utils.ajax({
				nonce: script_vars.nonce,
				action: 'diagnose',
				data: null,
				success: (response) => {
					if (response.success) {
						const id = response.id;

						this.showDiagnosis(id);
					}
					else {
						this.setDiagnosing(false);

						console.error('[Syscoin] Diagnosis request error:', response);
						alert(response.message);
					}
				},
				error: (xhr, status, error) => {
					this.setDiagnosing(false);

					console.error('[Syscoin] Diagnosis request ajax error:', xhr.statusText);
				}
			});
		},
		setSchedule: async (enable) => {
			this.setSaving(true);

			await utils.ajax({
				nonce: script_vars.nonce,
				action: 'setSchedule',
				data: { enable: enable },
				success: (response) => {
					if (!response.success) {
						console.error('[Syscoin] Schedule request error:', response);
						alert(response.message);
					}

					this.setSaving(false);
				},
				error: (xhr, status, error) => {
					this.setSaving(false);

					console.error('[Syscoin] Schedule request ajax error:', xhr.statusText);
				}
			});
		}
	};

	setListeners() {
		this.elements.actions.requestDiagnosis.on('click', () => {
			this.requests.diagnose();
		});

		this.elements.actions.back.on('click', () => {
			this.backToOverview();
		});

		this.elements.inputs.scheduleScan.on('click', (event) => {
			const isChecked = jQuery(event.currentTarget).is(':checked');

			this.requests.setSchedule(isChecked);
		});

		jQuery('.topic-row').on('click', function () {
			var target = jQuery(this).data('target');
			jQuery('#' + target).toggle();

			var arrow = jQuery(this).find('.toggle-arrow');
			if (arrow.css('transform') === 'none' || arrow.css('transform') === 'matrix(1, 0, 0, 1, 0, 0)') {
				arrow.css('transform', 'rotate(180deg)');
			} else {
				arrow.css('transform', 'rotate(0deg)');
			}
		});

		jQuery('.syscoin-diagnostics-fix').on('click', function () {
			var target = jQuery(this).data('target');

			console.log('Fixing for', target);

			if (script_vars.is_customer) {
				console.log('User is a customer.');
			}
			else {
				window.open('https://syscoin.com.br/contato/', '_blank');
			}
		});
	};

	fillHomePage() {
		this.elements.inputs.scheduleScan.prop('checked', script_vars.scheduled);

		this.elements.spinners.diagnosing.hide();
		this.elements.spinners.saving.hide();

		this.elements.spots.allListTitles.each(function () {
			var fullId = jQuery(this).attr('id');

			var match = fullId.match(/syscoin-diagnostics-list-title-id-(\d+)/);

			if (match && match[1]) {
				var timestamp = parseInt(match[1], 10) * 1000;
				var date = new Date(timestamp);

				var localizedDate = date.toLocaleString(undefined, {
					weekday: 'long',
					year: 'numeric',
					month: 'long',
					day: 'numeric',
					hour: 'numeric',
					minute: 'numeric',
					second: 'numeric'
				});

				jQuery(this).text(localizedDate);
			}
		});

		this.elements.spots.allListButtons.on('click', (event) => {
			var button = event.currentTarget;

			var fullId = jQuery(button).attr('id');

			var match = fullId.match(/syscoin-diagnostics-list-open-id-(\d+)/);

			if (match && match[1]) {
				var timestamp = match[1];

				this.showDiagnosis(timestamp);
			}
		});

		this.charts.line('scoreHistoryLineChart', script_vars?.history);
	}

	fillReportPage() {
		const dateString = this.elements.spots.reportDate.text();

		const url = new URL(window.location.href);
		const timestamp = parseInt(url.searchParams.get('id'), 10) * 1000;

		const date = new Date(timestamp);

		var localizedDate = date.toLocaleString(undefined, {
			weekday: 'long',
			year: 'numeric',
			month: 'long',
			day: 'numeric',
			hour: 'numeric',
			minute: 'numeric',
			second: 'numeric'
		});

		const replacedDateString = dateString.replace('-REPORT_DATE-', localizedDate);

		this.elements.spots.reportDate.text(replacedDateString);
	}

	showDiagnosis(id) {
		const url = new URL(window.location.href);
		url.searchParams.set('id', id);
		window.location.href = url.toString();
	}

	backToOverview() {
		const url = new URL(window.location.href);
		url.searchParams.delete('id');
		window.location.href = url.toString();
	}

	setDiagnosing(diagnosing) {
		if (diagnosing) {
			this.elements.actions.requestDiagnosis.attr('disabled', 'disabled');
			this.elements.inputs.scheduleScan.attr('disabled', 'disabled');
			this.elements.spinners.diagnosing.show();
		}
		else {
			this.elements.actions.requestDiagnosis.removeAttr('disabled');
			this.elements.inputs.scheduleScan.removeAttr('disabled');
			this.elements.spinners.diagnosing.hide();
		}
	}

	setSaving(saving) {
		if (saving) {
			this.elements.actions.requestDiagnosis.attr('disabled', 'disabled');
			this.elements.inputs.scheduleScan.attr('disabled', 'disabled');
			this.elements.spinners.saving.show();
		}
		else {
			this.elements.actions.requestDiagnosis.removeAttr('disabled');
			this.elements.inputs.scheduleScan.removeAttr('disabled');
			this.elements.spinners.saving.hide();
		}
	}

	charts = {
		destroyAll: () => {
			this.elements.chartjs.forEach((chartInstance) => {
				chartInstance.destroy();
			});
		},
		line: (id, data) => {
			const instance = new Chart( // @ts-ignore
				document.getElementById(id).getContext('2d'),
				{
					type: 'line',
					data: {
						datasets: [{
							data: data,
							borderColor: '#fbb73a',
							borderWidth: 5,
							pointRadius: 2,
							pointHitRadius: 10,
							pointHoverRadius: 10,
							fill: false,
							lineTension: 0.3,
						}]
					},
					options: {
						scales: { // @ts-ignore
							x: { type: 'time', time: { unit: 'day' }, },
							y: { min: 0, max: 100 }
						},
						plugins: { legend: { display: false } },
						responsive: true,
					},
				}
			);

			this.elements.chartjs.push(instance);
		},
	};
};

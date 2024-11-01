// @ts-check

var script_vars; // SUPPLIED BY PHP BACKEND

const logsViewerController = new class {
	constructor() {
		this.forceVerticalScrollbar();
		this.setListeners();
		this.requests.getLogs(undefined, 100);
		this.setFetching(false);
	};

	forceVerticalScrollbar() {
		const style = document.createElement('style');

		style.innerHTML = `
			html {
				overflow-y: scroll;
			}
		`;

		document.head.appendChild(style);
	}

	elements = {
		actions: {
			loadMore: jQuery('#syscoin-logs-load'),
		},
		spots: {
			logsTable: document.querySelector("#syscoin-logs-table tbody"),
			count: jQuery('#syscoin-logs-count')
		},
		spinners: {
			fetching: jQuery('#syscoin-logs-spinner-fetching'),
		},
		chartjs: [],
	};

	/**
	 * @type {Array<{
	 * 	original: string; 
	 * 	summarized: string; 
	 * 	time: number; 
	 * 	type: string;
	 * 	}>}
	 */
	crudeData = [];

	requests = {
		getLogs: async (until = new Date().getTime(), count = 100) => {
			this.setFetching(true);

			await utils.ajax({
				nonce: script_vars.nonce,
				action: 'getLogs',
				data: {
					until: until,
					count: count,
					filters: JSON.stringify(this.hiddenTypes)
				},
				success: (response) => {
					this.setFetching(false);

					if (response.success) {
						this.crudeData.push(...response.logs);

						this.populateTable(response.logs);
						this.populateChart(this.crudeData);

						this.filterData();

						if (response.logs.length < count) {
							this.elements.actions.loadMore.hide();
						}

						utils.finishLoading();
					}
					else {
						console.error('[syscoin] Logs request error:', response);
						alert(response.message);
					}
				},
				error: (xhr, status, error) => {
					this.setFetching(false);

					console.error('[syscoin] Logs request ajax error:', xhr.statusText);
				}
			});
		}
	};

	setListeners() {
		this.elements.actions.loadMore.on('click', () => {
			const oldestLogTime = this.crudeData[this.crudeData.length - 1].time;

			this.requests.getLogs(oldestLogTime - 1, this.getDisplayedCount() > 1000 ? 1000 : this.getDisplayedCount());
		});
	}

	setFetching(fetching) {
		if (fetching) {
			this.elements.spinners.fetching.show();
		}
		else {
			this.elements.spinners.fetching.hide();
		}
	}

	formatTime(unixTimestamp) {
		const date = new Date(unixTimestamp * 1000); // Multiply by 1000 to convert to milliseconds
		return date.toLocaleString();
	}

	formatTimeExtense(unixTimestamp) {
		const date = new Date(unixTimestamp * 1000); // Multiply by 1000 to convert to milliseconds

		return new Intl.DateTimeFormat(undefined, {
			weekday: 'long',
			year: 'numeric',
			month: 'long',
			day: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
			second: '2-digit',
			timeZoneName: 'long'
		}).format(date);
	}

	getEmojiForType(type) {
		switch (type) {
			case 'fatal':
				return '❌';  // Fatal error
			case 'warning':
				return '⚠️';  // Warning
			case 'notice':
				return '💬';  // Notice
			default:
				return '❓';  // Other/Unknown
		}
	}

	getColorForType(type) {
		switch (type) {
			case 'fatal':
				return '#ffe6e6';  // Fatal error
			case 'warning':
				return '#fff9e6';  // Warning
			case 'notice':
				return '#e6f0ff';  // Notice
			default:
				return '#ffffff';
		}
	}

	resetTable() {
		this.elements.spots.logsTable.innerHTML = '';
	}

	populateTable(data) {
		const table = this.elements.spots.logsTable;

		data.forEach(error => {
			// Create the log row using template literals
			const row = document.createElement('tr');
			row.style.cursor = 'pointer';

			const cell = document.createElement('td');
			cell.style.padding = '12px';
			cell.style.borderBottom = '1px solid #ddd';

			// Use template literals for the content inside the row
			cell.innerHTML = `
				<div style="display: flex; align-items: center; width: 1000px; user-select: none;">
					<div style="display: flex; width: 22.5%;">
						<div class="syscoin-rounded" style="width: fit-content; font-size: 18px; background-color: ${this.getColorForType(error.type)};">
							${this.getEmojiForType(error.type)}
						</div>

						<div class="syscoin-rounded" style="flex-grow: 1; margin: 0 12px; white-space: nowrap; font-weight: 500;" title="${this.formatTimeExtense(error.time)}">
							${this.formatTime(error.time)}
						</div>
					</div>

					<div style="display: flex; width: 72.5%;">
						<div class="syscoin-rounded" style="padding-left: 10px; background-color: ${this.getColorForType(error.type)};">
							<div style="font-family: monospace, monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
								${error.summarized}
							</div>
						</div>
					</div>

					<div style="display: flex; width: 5%; justify-content: center;">
						<img class="syscoin-toggle-arrow" src="${script_vars.assets.expand}" alt="Expand details" style="width: 24px; height: 24px; margin-left: 12px;">
					</div>
				</div>
			`;

			row.appendChild(cell);

			// Create the hidden details row using template literals
			const detailsRow = document.createElement('tr');
			detailsRow.style.display = 'none'; // Initially hidden

			// Use innerHTML for details row as well
			detailsRow.innerHTML = `
				<td colspan="3" style="padding: 12px; border-bottom: 1px solid #ddd; background-color: #f9f9f9; white-space: normal;">
					<div style="width: 1000px;">
						<div style="text-align: center; font-weight: 500;"> ${this.formatTimeExtense(error.time)} </div>
						<div class="syscoin-rounded" style="display: block; font-family: monospace, monospace; margin: 10px 0; background-color: ${this.getColorForType(error.type)};">
							${error.original}
						</div>
					</div>
				</td>
			`;

			// Toggle details row visibility on click
			row.addEventListener('click', function () {
				detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';

				var arrow = jQuery(this).find('.syscoin-toggle-arrow');
				if (arrow.css('transform') === 'none' || arrow.css('transform') === 'matrix(1, 0, 0, 1, 0, 0)') {
					arrow.css('transform', 'rotate(180deg)');
				}
				else {
					arrow.css('transform', 'rotate(0deg)');
				}
			});

			// Append both rows (log row and details row) to the table body
			table.appendChild(row);
			table.appendChild(detailsRow);

		});

		this.elements.spots.count.html((index, html) => {
			return html.replace(/\b\d+\b/g, (table.childElementCount / 2).toString());
		});
	}

	getDisplayedCount() {
		return this.elements.spots.logsTable.childElementCount / 2 || 0;
	}

	populateChart(data) {
		this.charts.line('logsCountLineChart', data);
	}

	filterData() {
		const filteredData = this.crudeData.filter(item => !this.hiddenTypes.includes(item.type));

		this.resetTable();
		this.populateTable(filteredData);
	}

	hiddenTypes = ['warning', 'notice'];

	charts = {
		destroyAll: () => {
			this.elements.chartjs.forEach((chartInstance) => {
				chartInstance.destroy();
			});
		},
		line: (id, data) => {
			const colors = { fatal: '#ec5866', warning: '#fdbe31', notice: '#53a0d4' };

			const lineOptions = {
				borderWidth: 5,
				tension: 0.3,
				pointRadius: 0,
				pointHitRadius: 10,
				pointHoverRadius: 10,
				fill: false
			};

			let granularity;

			const helpers = {
				setGranularity: function (minTime, maxTime) {
					const timeRange = maxTime - minTime; // in seconds
					if (timeRange <= 2 * 60 * 60) {
						// Less than or equal to 2 hours
						granularity = 'minute';
					} else if (timeRange <= 2 * 24 * 60 * 60) {
						// Less than or equal to 2 days
						granularity = 'hour';
					} else {
						granularity = 'day';
					}
				},

				getStartOfUnit: function (timestamp, granularity) {
					const date = new Date(timestamp * 1000);
					if (granularity === 'day') {
						date.setHours(0, 0, 0, 0);
					} else if (granularity === 'hour') {
						date.setMinutes(0, 0, 0);
					} else if (granularity === 'minute') {
						date.setSeconds(0, 0);
					}
					return date;
				},

				groupDataByType: function (data, granularity) {
					return data.reduce((acc, obj) => {
						const unit = helpers.getStartOfUnit(obj.time, granularity);
						const unitString = unit.toISOString();
						const type = obj.type;

						if (!acc[type]) acc[type] = {};
						acc[type][unitString] = (acc[type][unitString] || 0) + 1;

						return acc;
					}, {});
				},

				getAllUnits: function (minTime, maxTime, granularity) {
					const allUnits = [];
					let currentUnit = helpers.getStartOfUnit(minTime, granularity);
					const endUnit = helpers.getStartOfUnit(maxTime, granularity);

					while (currentUnit <= endUnit) {
						allUnits.push(new Date(currentUnit));
						if (granularity === 'day') {
							currentUnit.setDate(currentUnit.getDate() + 1);
						} else if (granularity === 'hour') {
							currentUnit.setHours(currentUnit.getHours() + 1);
						} else if (granularity === 'minute') {
							currentUnit.setMinutes(currentUnit.getMinutes() + 1);
						}
					}
					return allUnits;
				},

				prepareDatasets: function (data) {
					const timestamps = data.map(item => item.time);
					const minTime = Math.min(...timestamps);
					const maxTime = Math.max(...timestamps);

					helpers.setGranularity(minTime, maxTime);

					const allUnits = helpers.getAllUnits(minTime, maxTime, granularity);
					const countsPerUnitByType = helpers.groupDataByType(data, granularity);

					return Object.keys(colors).map(type => {
						const typeData = allUnits.map(unit => {
							const unitString = unit.toISOString();
							return {
								x: unit,
								y: countsPerUnitByType[type]?.[unitString] || 0
							};
						});

						return {
							label: type.charAt(0).toUpperCase() + type.slice(1),
							data: typeData,
							borderColor: colors[type],
							...lineOptions
						};
					});
				},
			};

			const datasets = helpers.prepareDatasets(data);

			// Apply hiddenLabels: Hide datasets whose labels are in the hiddenLabels array
			datasets.forEach(dataset => {
				if (this.hiddenTypes.map(label => label.toLowerCase()).includes(dataset.label.toLowerCase())) {
					dataset['hidden'] = true;
				}
			});

			this.charts.destroyAll();

			const instance = new Chart( // @ts-ignore
				document.getElementById(id).getContext('2d'),
				{
					type: 'line',
					data: { datasets: datasets },
					options: {
						scales: { // @ts-ignore
							x: { type: 'time', time: { unit: granularity, }, },
							y: { beginAtZero: true, }
						},
						plugins: {
							legend: {
								display: false,
								position: 'bottom',
								labels: {
									boxWidth: 30,
									boxHeight: 4,
									useBorderRadius: true,
									borderRadius: 2,
									padding: 20
								},
								onClick: (e, legendItem) => {
									const chart = e.chart;
									const index = legendItem.datasetIndex;
									const datasets = chart.data.datasets;

									// Check if the clicked dataset is already the only one visible
									const isOnlyVisible = datasets.every((dataset, i) => {
										const meta = chart.getDatasetMeta(i);
										const visible = !meta.hidden;
										return i === index ? visible : !visible;
									});

									if (isOnlyVisible) {
										// Show all datasets
										datasets.forEach((dataset, i) => {
											chart.getDatasetMeta(i).hidden = false;
										});
									} else {
										// Hide all datasets except the clicked one
										datasets.forEach((dataset, i) => {
											chart.getDatasetMeta(i).hidden = i === index ? false : true;
										});
									}

									chart.update();

									this.hiddenTypes = datasets.reduce((acc, dataset, i) => {
										const meta = chart.getDatasetMeta(i);
										if (meta.hidden) {
											acc.push(dataset.label.toLowerCase());
										}
										return acc;
									}, []);

									this.filterData();
								}
							},
						},
						responsive: true,
					}
				}
			);

			this.elements.chartjs.push(instance);
		}
	};
};

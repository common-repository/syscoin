// @ts-check

var script_vars; // SUPPLIED BY PHP BACKEND

const utils = {
	/**
	 * Make an Ajax request to the WordPress backend.
	 *
	 * @param {{
	 * 	nonce: string;
	 * 	action: string;
	 * 	data?: object;
	 * 	success?: (response) => any;
	 * 	error?: (xhr, status, error) => any
	 * }} request - Settings for the Ajax request.
	 */
	ajax: async (request) => {
		var ajaxData = {
			'action': request.action,
			'nonce': request.nonce,
			...request.data
		};

		await jQuery.ajax({
			type: 'POST',
			url: script_vars.ajax_url,
			data: ajaxData,
			success: (response) => request.success(response),
			error: (xhr, status, error) => request.error(xhr, status, error)
		});
	},

	hoursMinutesToGMT: (time) => {
		const date = new Date(`January 1, 1970 ${time}:00`);

		const utcHours = date.getUTCHours();
		const utcMinutes = date.getUTCMinutes();

		const formattedHours = (utcHours < 10 ? '0' : '') + utcHours;
		const formattedMinutes = (utcMinutes < 10 ? '0' : '') + utcMinutes;

		return formattedHours + ':' + formattedMinutes;
	},

	hoursMinutesToLocal: (time) => {
		if (!time) return null;

		const today = new Date();
		const dateString = today.toISOString().substring(0, 10);

		const fullGMTDateString = dateString + 'T' + time + ':00Z';

		const dateInGMT = new Date(fullGMTDateString);

		const localHours = dateInGMT.getHours();
		const localMinutes = dateInGMT.getMinutes();

		const formattedLocalHours = (localHours < 10 ? '0' : '') + localHours;
		const formattedLocalMinutes = (localMinutes < 10 ? '0' : '') + localMinutes;

		return formattedLocalHours + ':' + formattedLocalMinutes;
	},

	tz: () => {
		return Intl.DateTimeFormat().resolvedOptions().timeZone;
	},

	finishLoading: () => {
		jQuery('.syscoin-initially-hidden').show();
		jQuery('.syscoin-initially-shown').hide();
	},

	/**
	 * See if two objects have the same contents.
	 *
	 * @param {any} a
	 * @param {any} b
	 * @return {boolean} 
	 */
	deepEqual: (a, b) => {
		// Check if both values are identical (including type and value)
		if (a === b) {
			return true;
		}

		// Check if both values are objects and not null
		if (typeof a !== 'object' || a === null || typeof b !== 'object' || b === null) {
			return false;
		}

		// Get the keys of both objects
		const keys1 = Object.keys(a);
		const keys2 = Object.keys(b);

		// Check if the number of keys is different
		if (keys1.length !== keys2.length) {
			return false;
		}

		// Check if all keys and values are the same
		for (let key of keys1) {
			// Check if the key exists in obj2
			if (!keys2.includes(key)) {
				return false;
			}
			// Recursively compare values
			if (!utils.deepEqual(a[key], b[key])) {
				return false;
			}
		}

		return true;
	},

	/**
	 * Restores initially-hidden and initially-shown to their original behavior. Use this when you want the page to display the loading animation again.
	 */
	startLoading: () => {
		jQuery('.syscoin-initially-hidden').hide();
		jQuery('.syscoin-initially-shown').show();
	},

	/**
	 * Returns a random HEX color that's pseudorandom based on a string received.
	 *
	 * @param {string} name
	 */
	getHashColor: (name) => {
		if (!name || name.startsWith("No ")) {
			return '#253137';
		}

		let hash = 0;
		for (let i = 0; i < name.length; i++) {
			hash = name.charCodeAt(i) + ((hash << 5) - hash);
		}

		let color = '#';
		for (let i = 0; i < 3; i++) {
			// Shift bits and ensure color is light (between 100 and 255)
			const value = (hash >> (i * 8)) & 0xFF;
			const lightValue = Math.max(100, value); // Ensures it's not too dark
			color += ('00' + lightValue.toString(16)).slice(-2);
		}

		return color;
	},

	convertDateKeysToLocale: (obj) => {
		const now = new Date();
		const result = {};

		for (let key in obj) {
			if (Object.prototype.hasOwnProperty.call(obj, key)) {
				let value = obj[key];

				if (/^\d{4}-\d{2}-\d{2}$/.test(key)) {
					let date = new Date(key);

					let formattedKey = date.toLocaleDateString(undefined, { timeZone: 'UTC' });

					if (now < date) {
						result[formattedKey] = null;
					}
					else {
						result[formattedKey] = value;
					}
				}
				else {
					result[key] = value;
				}
			}
		}

		return result;
	}
};

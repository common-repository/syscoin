// @ts-check

/**
 * Represents the admin script for the Syscoin plugin.
 */
const adminScript = new class {
	constructor() {
		console.log(`[Syscoin ${script_vars.pluginVersion}] Token status:`, script_vars.tokenStatus, new Date(script_vars.tokenStatusTimestamp * 1000));

		if (script_vars.tokenStatus === 'INVALID') {
			alert('You have been logged out of Syscoin. Please log in again.');

			location.reload();
		}

		this.loadSalveChat();
	}

	async loadSalveChat() { // somente para Syscoin
		window['chatwootSettings'] = { "position": "right", "type": "standard", "launcherTitle": "Fale conosco no chat" };

		(function (d, t) {
			var BASE_URL = "https://app.salve.chat";
			var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
			// @ts-ignore
			g.src = BASE_URL + "/packs/js/sdk.js";
			// @ts-ignore
			g.defer = true;
			// @ts-ignore
			g.async = true;
			s.parentNode.insertBefore(g, s);
			g.onload = function () {
				// @ts-ignore
				window.chatwootSDK.run({
					websiteToken: 'vdB3qiUZXT3XU2FAjBuJev9c',
					baseUrl: BASE_URL
				});
			};
		})(document, "script");
	}
};

// @ts-check

var script_vars; // SUPPLIED BY PHP BACKEND

// CONTROLLER
/**
 * This class represents the controller for the product metabox.
 */
const metaboxController = new class {
	constructor() {
		this.prepareMetaBox();

		utils.finishLoading();
	}

	/**
	 * List of jQuery references to the elements that are part of the plugin's metabox.
	 */
	elements = {
		allInputsAndActions: jQuery('[id^="syscoin-"]').filter('input, button, select'),
		versions: {
			forNotLoggedIn: jQuery('#syscoin-product-metabox-for-non-logged-in'),
			forNotPremium: jQuery('#syscoin-product-metabox-for-non-premium'),
			forPremium: jQuery('#syscoin-product-metabox-for-premium'),
			forNoToken: jQuery('#syscoin-product-metabox-for-no-token'),
			content: jQuery('#syscoin-product-metabox-generator-content'),
		},
		messages: {
			wordCountNumber: jQuery('#syscoin-word-count-display'),
		},
		inputs: {
			wordCount: jQuery('#syscoin-word-count-slider'),
			draft: jQuery('#syscoin-ai-desc-draft'),
			language: jQuery('#syscoin-language-selector'),
		},
		actions: {
			generateLongDesc: jQuery('#syscoin-button-gen-ai-desc'),
			generateShortDesc: jQuery('#syscoin-button-gen-ai-desc-short'),
		},
		spinners: {
			loading: jQuery('#syscoin-metabox-spinner'),
		},
		/*
		 * These are functions because most elements can't be queried immediately when the page loads.
		 */
		external: {
			title: () => jQuery('#title'),
			longDescVisual: () => jQuery(jQuery('#content_ifr').contents()).find('#tinymce'),
			longDescText: () => jQuery('#content'),
			shortDescVisual: () => jQuery(jQuery('#excerpt_ifr').contents()).find('#tinymce'),
			shortDescText: () => jQuery('#excerpt'),
			metaboxSortables: () => jQuery('.meta-box-sortables'),
		}
	};

	/**
	 * Prepares the metabox for the product creation screen.
	 */
	prepareMetaBox() {
		this.setLoading(false);

		this.elements.versions.forNotLoggedIn.hide();
		this.elements.versions.forNotPremium.hide();
		this.elements.versions.forPremium.hide();
		this.elements.versions.forNoToken.hide();
		this.elements.versions.content.hide();

		if (!script_vars.is_logged_in) {
			this.elements.versions.forNotLoggedIn.show();

			return;
		}

		if (!script_vars.is_premium) {
			this.elements.versions.forNotPremium.show();

			return;
		}

		this.elements.versions.forPremium.show();

		if (!script_vars.has_openai_key) {
			this.elements.versions.forNoToken.show();

			return;
		}

		this.elements.versions.content.show();

		this.setListeners();
	}

	setListeners() {
		this.elements.inputs.wordCount.on('input', (a) => {
			this.elements.messages.wordCountNumber.text(this.elements.inputs.wordCount.val().toString());
		});

		this.elements.actions.generateLongDesc.on('click', async (event) => {
			event.preventDefault();

			const product = this.getProductInfo();

			const generated = await metaboxService.getAiDescription(
				product.title.toString(),
				product.categories,
				product.variations,
				product.draft.toString(),
				product.wordCount.toString(),
				product.language.toString()
			);

			this.concatenateLongDescription(generated);
		});

		this.elements.actions.generateShortDesc.on('click', async (event) => {
			event.preventDefault();

			const product = this.getProductInfo();

			const generated = await metaboxService.getAiDescription(
				product.title.toString(),
				product.categories,
				product.variations,
				product.draft.toString(),
				product.wordCount.toString(),
				product.language.toString()
			);

			this.concatenateShortDescription(generated);
		});

		this.elements.external.metaboxSortables().on('sortstop', () => {
			this.adjustMetaboxLayout();
		});

		this.adjustMetaboxLayout();
	}

	/**
	 * Sets the loading state of the product metabox.
	 * @param {boolean} loading - Indicates whether the metabox should be in a loading state or not.
	 */
	setLoading(loading) {
		if (loading) {
			this.elements.spinners.loading.show();
			this.elements.allInputsAndActions.attr('disabled', 'disabled');
		}
		else {
			this.elements.spinners.loading.hide();
			this.elements.allInputsAndActions.removeAttr('disabled');
		}
	}

	/**
	 * Retrieves the product elements from the DOM.
	 */
	getProductInfo() {
		return {
			title: this.elements.external.title().val(),
			draft: this.elements.inputs.draft.val(),
			wordCount: this.elements.inputs.wordCount.val(),
			categories: this.getProductCategories(),
			variations: this.getProductVariations(),
			language: this.elements.inputs.language.val(),
		};
	}

	/**
	 * Retrieves the selected product categories from the DOM.
	 * @returns {string[]} An array of selected category names.
	 */
	getProductCategories() {
		const selectedCategories = [];

		jQuery('#product_catdiv input:checked').each(function () {
			const category = jQuery(this).parent().contents().text().trim();

			selectedCategories.push(category);
		});

		return selectedCategories;
	}

	/**
	 * Retrieves the product variations from the DOM.
	 * @returns {string[]} An array of variation attributes.
	 */
	getProductVariations() {
		const variations = [];

		jQuery('.woocommerce_variation').each(function () {
			const variationAttributes = [];

			jQuery(this).find('h3 > select').each(function () {
				var selectedOption = jQuery(this).find('option:selected');

				variationAttributes.push(selectedOption.text());
			});

			variations.push(variationAttributes.join(' '));
		});

		return variations;
	}

	concatenateLongDescription(newDescription) {
		if (!newDescription) return;

		const currentDesc = this.elements.external.longDescText().val();

		const visual = currentDesc + (currentDesc ? '<br>' : '') + newDescription;
		const text = currentDesc + (currentDesc ? '\n' : '') + newDescription;

		this.elements.external.longDescVisual().empty().html(visual);
		this.elements.external.longDescText().val(text);
	}

	concatenateShortDescription(newDescription) {
		if (!newDescription) return;

		const currentDesc = this.elements.external.shortDescText().val();

		const visual = currentDesc + (currentDesc ? '<br>' : '') + newDescription;
		const text = currentDesc + (currentDesc ? '\n' : '') + newDescription;

		this.elements.external.shortDescVisual().empty().html(visual);
		this.elements.external.shortDescText().val(text);
	}

	adjustMetaboxLayout() {
		const generatorTitle = jQuery('#syscoin-product-metabox-first-title');
		const generatorContent = jQuery('#syscoin-product-metabox-generator-content');
		const generatorDraft = jQuery('#syscoin-product-metabox-generator-draft-container');
		const generatorCountLang = jQuery('#syscoin-product-metabox-generator-count-lang-container');
		const generatorCount = jQuery('#syscoin-product-metabox-generator-count');
		const generatorLang = jQuery('#syscoin-product-metabox-generator-lang');
		const generatorActions = jQuery('#syscoin-product-metabox-generator-actions');

		const isInRightColumn = jQuery('#syscoin-product-metabox').closest('#postbox-container-1').length > 0;

		if (isInRightColumn) {
			generatorTitle.css('flex-direction', 'column-reverse');
			generatorContent.css('flex-direction', 'column');
			generatorDraft.css('width', '100%');
			generatorCountLang.css('margin', '10px 0');
			generatorCount.css('margin-right', '0');
			generatorLang.css('margin-right', '0');
			generatorActions.css('flex-direction', 'column');
		}
		else {
			generatorTitle.css('flex-direction', 'row');
			generatorContent.css('flex-direction', 'row');
			generatorDraft.css('width', '50%');
			generatorCountLang.css('margin', '0');
			generatorCount.css('margin-right', '10px');
			generatorLang.css('margin-right', '10px');
			generatorActions.css('flex-direction', 'row');
		}
	}
};

// SERVICE
/**
 * This class represents the service for the product metabox.
 */
const metaboxService = new class {
	/**
	 * Generates an AI description for a product.
	 * 
	 * @param {string} productName - The name of the product.
	 * @param {string[]} categories - The categories of the product.
	 * @param {string[]} variations - The variations of the product.
	 * @param {string} draft - Initial draft for the product description.
	 * @param {string} amountOfWords - The desired amount of words in the description.
	 * @param {string} language - The language of the description.
	 * @returns {Promise<string>} A promise that resolves with the generated description.
	 */
	async getAiDescription(productName, categories, variations, draft, amountOfWords, language) {
		metaboxController.setLoading(true);

		if (!productName) {
			alert('Please enter a product name.');
			metaboxController.setLoading(false);
			return;
		}

		return new Promise((resolve, reject) => {
			utils.ajax({
				nonce: script_vars.nonce,
				action: 'generateAiDescription',
				data: {
					name: productName,
					categories: categories,
					variations: variations,
					draft: draft,
					amountOfWords: amountOfWords.toString(),
					language: language
				},
				success: (response) => {
					metaboxController.setLoading(false);

					if (response.success && response.description) {
						resolve(response.description);
					}
					else {
						alert('Could not generate a description at this time. Please try again later.');
						console.error('[Syscoin] Backend responded with invalid object:', response);
						reject(response);
					}
				},
				error: (xhr, status, error) => {
					metaboxController.setLoading(false);
					alert('Could not generate a description at this time. Please try again later.');
					console.error('[Syscoin] xhr error generating description:', xhr.statusText);
					reject(xhr.statusText);
				}
			});
		});
	}
};

function validateCC(ccNumber) {
	if (typeof use_cc_validation == 'undefined' || !use_cc_validation) {
		return true;
	}
	const allowedTypes = [
		'visa',
		'master-card',
		'mastercard'
	];
	const testCardNumbers = [
		"4111111111111114",  // Visa test card
		"4111111111111110",  // Visa test card
		"4111111111111111",  // Visa test card
		"4242424242424242",  // Stripe test card
		"5555555555554444",  // MasterCard test card
		"2223003122003222",  // Mastercard test card
		"5555555555551111",
		"4111111111111115",
		"4444444444444449",
		"4444444444444448",
		"5488549999999804",
		"5500000000000004",
		"5431111111111111",
		"6011601160116611"
	];

	const validation = cardValidator.number(ccNumber);
	var ccNoSpaces = ccNumber.replace(/\s+/g, '');
	if ((typeof validation != 'undefined') &&
		((validation.isValid && (typeof validation.card != 'undefined') && (allowedTypes.indexOf(validation.card.type) !== -1)) ||
		(testCardNumbers.indexOf(ccNoSpaces) !== -1))) {
		return true;
	}
	return false;
}

function sendCcStats(traffic_page_id, prospect_id, type) {
	if (typeof use_cc_validation == 'undefined' || !use_cc_validation) {
		return true;
	}
	var url = ((typeof root_path != 'undefined') ? root_path : '') + 'api/statistics.php';
	$.ajax({
		type: 'POST',
		url: url,
		data: {
			'action': 'stats-cc',
			'traffic_page_id': traffic_page_id,
			'prospect_id': prospect_id,
			'type': type
		},
		success: function (response) {
		}
	});
}

function checkCcExpDate(month, year) {
	var input = month + year;

	var inputMonth = parseInt(input.slice(0, 2), 10);
	var inputYear = 2000 + parseInt(input.slice(2, 4), 10);

	var now = new Date();
	var currentMonth = now.getMonth() + 1;
	var currentYear = now.getFullYear();

	return (inputYear > currentYear || (inputYear === currentYear && inputMonth >= currentMonth));
}
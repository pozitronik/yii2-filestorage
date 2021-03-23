/**
 * Загружает в тело существующей модалки новый контент из renderAjax-вью
 * @param {string} dataUrl
 * @param {string} modalDivId
 * @constructor
 */
function LoadModal(dataUrl, modalDivId) {
	let modal = $("#" + modalDivId),
		modalBody = modal.find('.modal-body');
	modalBody.load(dataUrl, function() {
		modal.modal('show');
	});
}

/**
 * Загружает renderPartial - вью в контейнер с прелоадером
 * @param {string} dataUrl
 * @param {string} modalDivId
 * @param {string} modalContainerId
 */
function AjaxModal(dataUrl, modalDivId, modalContainerId) {
	let modalContainerDiv;
	if (undefined === modalContainerId) {
		modalContainerId = 'modal-ajax-div';
	}
	modalContainerDiv = $("#" + modalContainerId);
	if (0 === modalContainerDiv.length) {
		modalContainerDiv = $('<div />', {
			'id': modalContainerId,
		});
		$('body').prepend(modalContainerDiv);
	}
	modalContainerDiv.addClass('preloading');
	modalContainerDiv.load(dataUrl, function() {
		$('#' + modalDivId).modal('show');
		modalContainerDiv.removeClass('preloading');
	}).show();
}
/**
 * @param inputElement
 * @param outputElement
 * unused
 */
function generateOnchangeAction(inputElement, outputElement) {
	let fileName = extractFileName(inputElement.val());//extractFileName определена в homer.js
	outputElement.html(fileName + '<br/><span class=\'small text-info\'>файл будет загружен</span>');
}
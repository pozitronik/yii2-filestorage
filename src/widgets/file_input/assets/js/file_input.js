/**
 * @param inputElement
 * @param outputElement
 */
function generateOnchangeAction(inputElement, outputElement) {
	let fileName = extractFileName(inputElement.val());//extractFileName определена в homer.js
	outputElement.html(fileName + '<br/><span class=\'small text-info\'>файл будет загружен</span>');
}

/**
 * @param {string} filePath
 * @returns {string}
 */
function extractFileName(filePath) {
	let startIndex = (filePath.indexOf('\\') >= 0?filePath.lastIndexOf('\\'):filePath.lastIndexOf('/')),
		filename = filePath.substring(startIndex);
	if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) filename = filename.substring(1);
	return filename;
}
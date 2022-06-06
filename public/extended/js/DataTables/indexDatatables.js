function toDatatables(language, path)
{
	var file = getFileLanguage(language);
	var dt =
	$('.doc_datatables').DataTable( {
		"oLanguage": {
			"sUrl": path+"/"+file
		},
		"fnInitComplete": function () {
			$("div.toolbar").html('<br />');
		},
		"sPaginationType": "full_numbers",
		"responsive": {
			"details": {
				display: $.fn.dataTable.Responsive.display.childRowImmediate,
				type: 'none',
				target: ''
			}
		},
		"aLengthMenu": [[5, 10, 25], [5, 10, 25]]
	});
	
	return dt;
}

function getFileLanguage(language)
{
	var file;
	switch(language)
	{
		case "fr":
			file = "dataTables.fr.txt";
			break;
		case "en":
			file = "dataTables.en.txt";
			break;
		case "es":
			file = "dataTables.es.txt";
	}
	return file;
}
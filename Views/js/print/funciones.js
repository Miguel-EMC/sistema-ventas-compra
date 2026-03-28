// Desarrollado por www.cesarcancino.com
function show(url)
				{
			document.getElementById('sombra').className='sombraLoad';
			document.getElementById('window').className='windowLoad';
			document.getElementById("mi_marco").src=url;
                 }

		function hide()
		{
			document.getElementById('sombra').className='sombraUnload';
			document.getElementById('window').className='windowUnload';
			document.getElementById("mi_marco").src="";
		}
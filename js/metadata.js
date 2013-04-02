
/**
Utilisation de l'objet XmlHttpRequest pour ajouter des champs.
*/
function getXhr(){
	var xhr = null; 
	if(window.XMLHttpRequest) // Firefox et autres
		xhr = new XMLHttpRequest(); 
	else if(window.ActiveXObject){ // Internet Explorer 
		try {
			xhr = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	else { // XMLHttpRequest non support� par le navigateur 
		alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest..."); 
		xhr = false; 
	} 
	return xhr
}

		
/**
* M�thode qui sera appel�e sur le click du bouton
*/
function go(dir, om, id){			
			var xhr = getXhr()
			// On d�fini ce qu'on va faire quand on aura la r�ponse
			
			var data = "omid=" + om + "&id=" +id;
			//alert(dir+"?"+data);
			
			xhr.open("GET",dir+"?"+data,true);
			
			xhr.send();
			
			alert("You left the meeting");
	
}
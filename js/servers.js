function addServer(i){
	
	var i2 = i+1;
	
	jsServer='<br/><table width="900" align="center"><tr><td width="90%"><fieldset><legend><b>Serveur #'+i+'</b></legend><br/><table align="center" width="500">';
	
	jsServer+='<tr><td>Hôte OpenMeetings/Red5 <br/>(example : votredomaine.com)</td><td><input type="text" name="nom'+i+'" value="" /></td></tr>';
	jsServer+='<tr><td>Adresse ip du serveur</td><td><input type="text" name="ip'+i+'" value="" /></td></tr>';
	jsServer+='<tr><td>Port HTTP <br/>(défaut : 5080 )</td><td><input type="text" name="port'+i+'" value="" /></td></tr>';
	jsServer+='<tr><td>Version du serveur OpenMeetings/Red5 <br/>(1.2, 1.3, etc.)</td><td><input type="text" name="version'+i+'" value="" /></td></tr>';
	jsServer+='<tr><td>Identifiant administrateur </td><td><input type="text" name="login'+i+'" value="" /></td></tr>';
	jsServer+='<tr><td>Mot de passe administrateur</td><td><input type="password" name="password'+i+'" value="" /></td></tr></table><br/></fieldset></td><td align="center"><input type="button" value="Supprimer" onClick="javascript:delServer('+i+')"/></td></tr></table>';
	

//	alert('server'+i0);
	if(document.getElementById('server'+i).innerHTML != ''){
	
		document.getElementById('server'+i).innerHTML = jsServer;
	
	}else{
	
	document.getElementById('server'+i).innerHTML = jsServer;
	if(i<=9){
		
			document.getElementById('server'+i).innerHTML += '<center><input id="addServer'+i2+'" type="button" value="Ajouter" onClick="this.style.display = \'none\';javascript:addServer('+i2+')"/></center>';
		
	}
	
	var mydiv = document.createElement("div");
	mydiv.setAttribute('id','server'+i2);
	document.getElementById('serverList').appendChild(mydiv);
	
	}
//	document.getElementById('server'+i).innerHTML += (i<=9) ? '<input type="button" value="Ajouter" onClick="this.style.display = \'none\';javascript:addServer('+i2+')"/><br/>';
	
}

function delServer(i){
	

	jsServer='';
	
//	alert('server'+i0);
	
	document.getElementById('server'+i).innerHTML = jsServer;
	if(i<=9){
		document.getElementById('server'+i).innerHTML += '<center><input type="button" value="Ajouter" onClick="this.style.display = \'none\';javascript:addServer('+i+')"/></center>';
	}
//	document.getElementById('server'+i).innerHTML += (i<=9) ? '<input type="button" value="Ajouter" onClick="this.style.display = \'none\';javascript:addServer('+i2+')"/><br/>';
	
}
//�������� �������
function getXmlHttp(){
 var xmlhttp;
 try {
 xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
 } catch (e) {
 try {
 xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
 } catch (E) {
 xmlhttp = false;
 }
 }
 if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
 xmlhttp = new XMLHttpRequest();
 }
 return xmlhttp;
}

//��������� ������� POST
function postajax(page,params,content_id,wait_id) {
	
	loadElem = document.getElementById(wait_id);
	loadElem.style.display = 'inline'; // �������� ��� "���������� �������"
    //������ ��� ������� � �������
    var req = getXmlHttp() 
	//��� ������������� ���������
	var docum = page+'?rnd='+Math.random()+'&'+params;
	//� ����� ����� ��������� ����� ��������
	var contentElem = document.getElementById(content_id);
	contentElem.style.display = 'inline';
	//��������� ����������	
    req.open('POST', docum, true);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	// onreadystatechange ������������ ��� ��������� ������ �������
    req.onreadystatechange = function() {  
 
        if (req.readyState == 4) { 
		
		// ���� ������ �������� �����������
            if(req.status == 200) { // ���� ������ 200 (��) - ������ ����� ������������
				var resText = req.responseText;
				//��� ���� ����� ��� ��� �������� � ������ FireFox'e
				var ua = navigator.userAgent.toLowerCase();
				if (ua.indexOf('gecko') != -1) {  // ���� ������� Mozilla, ��� Firefox, ��� Netscape
					
				  var range = contentElem.ownerDocument.createRange();
						 range.selectNodeContents(contentElem);	 // ������� ������������ ������ �����
						 range.deleteContents();
				  var fragment = range.createContextualFragment(resText); //<� dies here	// ������ �������� ����������� ���������
						contentElem.appendChild(fragment);
				}  else  {		 // ��� ��������� ���������
				  contentElem.innerHTML = resText;
				}
				loadElem.style.display = 'none';
            }
            else
			{
				content.innerHTML = '���������� ��������� ������!';
			}
        }
 
    }
 
    // ������ ������� �����������: ������ ����� � ������� ������� onreadystatechange
    // ��� ��������� ������ �������
 
    req.send(params);  // �������� ������
}

<?PHP

/** 
* Class unser_install
* 
* @date      09.02.2020
* @copyright MIT License 
* @author    Daniel Rueegg Winterthur CH
* 
* Built to set up Typo3 installations on hosted servers without ssh-access.
*
**/

class user_install {

    /**
    * Property strVersion
    *
    * @var string
    */
    Private $strVersion = '2.09a';

    /**
    * Property strDocupassword
    *  essential for successfull login to this tool!
    *  
    *  create a new password: 
    *  use first the command 'sha1 Passwort' to create a new hash.
    *  Copy it and and paste the created hash here.
    *
    * @var string
    */
    Private $strDocupassword = 'b858cb282617fb0956d960215c8e84d1ccf909c6';//user

    /**
    * Property strSecretPreauthKey
    *  essential for successfull preauthLogin!
    *
    * @var string
    */
    Private $strSecretPreauthKey = 'theSecretKeyFromMBA-ITforIntranetSekII';

    /**
    * Property strAuthSeparer
    *  essential for successfull preauthLogin!
    *
    * @var string
    */
    Private $strAuthSeparer = 'singleSeparerLike-_/|';

    /**
    * Property aIngredients
    *  the field-order is a secret and essential for successfull preauthLogin!
    *
    * @var array
    */
    Private $aIngredients = [
                'school' => 'sfgz',
                'role' => 'teacher',
                'timestamp' => 0,
                'account' => 'vorname.nachname',
                'preauth' => '',
            ];

    /**
    * Property Aktionen
    *
    * @var array
    */
    Private $Aktionen = [
        'u'=>[ 'titel'=>'Typo3-Datei entpacken',  'felder'=>'pwd,original,subpfad',    'script' => 'actUnzip' ] , 
        'l'=>[ 'titel'=>'Symlink erzeugen',       'felder'=>'pwd,linkdatei,symlink',   'script' => 'actLink' ] ,
        'd'=>[ 'titel'=>'Symlink l&ouml;schen',   'felder'=>'pwd,symlink',             'script' => 'actDeletelink' ] , 
        'f'=>[ 'titel'=>'Dateiliste',             'felder'=>'pwd,fileinfotext',            'script' => 'actFileInfo' ] , 
        'a'=>[ 'titel'=>'Preauth Links anzeigen', 'felder'=>'pwd,username,subdomains', 'script' => 'actPreauth' ] ,
        'p'=>[ 'titel'=>'Passwort &auml;ndern',   'felder'=>'pwd,passwort,passinfo',   'script' => 'actPassword' ]
    ];
    
    /**
    * Property Felder
    *
    * @var array
    */
    Private $Felder = [
        'aktion'    => [
                        'typ'=>"select" ,   
                        'lab'=>'Aktion' ,            
                        'listen'=>'aktList' , 
                        'standardwert'=>'f'
                        ],
        'subdomains'=> [
                        'typ'          => 'text' ,   
                        'lab'          => 'Dom&auml;nenliste',  
                        'tiptext'      => ' URLs mit Preauth.' ,   
                        'standardwert' => 'https://subdomain.mydomain.ch'
                        ],
        'username'  => [
                        'typ'           => 'text' ,     
                        'lab'           => 'Benutzername',       
                        'tiptext'       => ' eigener Vor- und Nachname',    
                        'standardwert'  => 'vorname.nachname'
                        ],
        'passwort'  => [
                        'typ'=>'text' ,     
                        'lab'=>'neues Passwort',     
                        'tiptext'=>' zum&nbsp;verschl&uuml;sseln&nbsp;'
                        ],
        'pwd'       => [
                        'typ'=>'password' , 
                        'lab'=>'Passwort'
                        ],
        'original'  => [
                        'typ'=>'select' ,   
                        'lab'=>'Original-Datei' ,    
                        'listen'=>'fileList'
                        ],
        'subpfad'   => [
                        'typ'=>"select" ,   
                        'lab'=>'zu Verzeichnis' ,    
                        'listen'=>'subDirList'
                        ],
        'linkdatei' => [
                        'typ'=>"text" ,     
                        'lab'=>'Linkdatei' ,         
                        'tiptext'=>'1. ../t3Sources/typo3_src-9.5.5 | 2. typo3_src/typo3 | 3. typo3_src/index.php' , 
                        'standardwert'=>'../t3Sources/typo3_src-9.5.5'
                        ],
        'symlink'   => [
                        'typ'=>"text" ,     
                        'lab'=>'Symlink' ,           
                        'tiptext'=>'1. typo3_src | 2. typo3 | 3. index.php' ,  
                        'standardwert'=>'subfolder/typo3_src'           
                        ],
        'fileinfotext'    => [
                        'typ'=>"label" , 
                        'lab'=>'Hinweis' , 
                        'text'=>'Zeigt alle Dateien im aktuellen Pfad und Aktionen, die mit t3InstallHelp ausgef&uuml;hrt werden k&ouml;nnen.'
                        ],
        'passinfo'    => [
                        'typ'=>"label" , 
                        'lab'=>'Hinweis' , 
                        'text'=>'Passwort ändern: Dieses Script mit einem Editor &ouml;ffnen und den hier erstellten hash als Wert für die Variable $strDocupassword einsetzen. '
                        ]
    ];
    

    /**
    * Property mim
    *
    * @var array
    */
    Private $mim = [
            'zip' => [ 'cmd' => 'unzip' ,     'opt' => '-d' ],
            'tgz' => [ 'cmd' => 'tar -zxvf' , 'opt' => '-C' ],
            'gz'  => [ 'cmd' => 'tar -zxvf' , 'opt' => '-C' ]
    ];
    
    /**
    * Property Form
    *
    * @var array
    */
    Private $Form = ['charset'=>'ISO-8859-1','name'=>'installform'];
    
    /**
    * Property req
    *
    * @var array
    */
    Private $req;
    
    /**
    * Property Pfade
    *
    * @var array
    */
    Private $Pfade;
    
    /**
     * start_user_install
     *   initiate script
     *   returns string with final HTML code
     *
     * @return string
     */
    Public function start_user_install(){
        // detect paths
        $this->Pfade['original'] = rtrim( dirname(__FILE__), "/")."/";
        $this->Pfade['basis'] = rtrim($_SERVER['DOCUMENT_ROOT'],"/")."/";
        
        // get incomed values
        foreach(array_keys($this->Felder) as $inVar){
                if(!empty($_REQUEST[$inVar])){
                    
                    $this->req[$inVar] = $_REQUEST[$inVar];
                }
        }
        
        // create the input form part of html document
        $bodyOut = $this->htmFormular();
        
        // if ok was clicked then run data-Action, wrap it as html div and append to form.
        if(isset($_POST['ok'])){
            $bodyOut .= '<div style="border-top:thin solid #ccc; padding:10px 0 0 0;margin:10px 0 0 0;" >';
            $bodyOut .= $this->runAction();
            $bodyOut .= '</div>';
        }
        
        // output header , form, runAction()-result and footer
        $htmlOut = $this->wrapAsHtml($bodyOut);
        
        return $htmlOut;
        
    }
    
    /**
     * wrapAsHtml
     *  enritch the content with different wrap if logged in
     *
     * @param string $content
     * @return string
     */
    Private function wrapAsHtml( $content ){
        if(!isset($this->req['aktion'])){
            $aktion = $this->Felder['aktion']['standardwert'];
        }else{
            $aktion = $this->req['aktion'];
        }
        $loginTest = $this->loginTest();
        $tit = $loginTest > 0 ? 't3installHelp | ' . $this->Aktionen[$aktion]['titel']: 'Login to t3 Install Helper';
        
        $header = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n  \"http://www.w3.org/TR/html4/loose.dtd\">";
        $header.= "<html><head><title>".$tit."</title></head>";
        
        $protocoll = 'localhost' == $_SERVER['SERVER_NAME'] ? 'http://' : 'https://';
        $URL = $protocoll . $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
        
        $body = '  <body>';
        $body.= '    <div style="margin:10px auto;max-width:1150px;">';
        $body.= '      <div style="min-width:570px;max-width:1150px;border:1px solid #AAA;border-radius:6px;margin:20px 5px; background:#e9edef; padding:10px 8px;">';
        $body.= '        <h2 style="margin:0 0 10px 0;padding: 0 0 5px 0; border-bottom:thin solid #aaa;">';
        $body.= '          <span style="font-variant-caps: small-caps;">t3 Install Helper</span>';
        $body.= '          <span style="font-size:75%;font-weight:normal;">v' . $this->strVersion . '</span>';
        $body.= '          <span style="font-size:50%;font-weight:normal;"> &copy;' . date('Y') . ' MIT Daniel R&uuml;egg</span>';
        if( $loginTest > 0 ) $body.= '          <span style="font-size:75%;font-weight:normal;"><a href="'. $URL. '">Logout</a></span>';
        
        if( $loginTest > 0 ) {
                $body.= '<p style="font-size:50%;font-weight:normal;padding:0;margin:5px 0;">';
                $body.= ' Diese Datei';
                $body.= ' &raquo;' . pathinfo( __FILE__ , PATHINFO_BASENAME). '&laquo;';
                $body.= ' nach Gebrauch vom Webspace entfernen!';
                $body.= ' Standort: ' . dirname( __FILE__ ) . '/';
                $body.= '</p>';
        }
        $body.= '        </h2>';

        if( $loginTest <= 0 ) $body.= '        <p>&larr; <a href="/">zur&uuml;ck</a></p>';

        $body.= ''.$content.'';
        
        if( $loginTest > 0 ) {
                $body.= '<p style="border-top:1px solid #aaa;font-size:80%;padding:10px 0 0 0;margin:15px 0 0 0;font-style:italic;;font-weight:normal;">';
                $body.= ' Built to set up Typo3 installations on hosted servers without ssh-access';
                $body.= '</p>';
        }
        
        $body.= '      </div>';
        $body.= '    </div>';
        $body.= '  </body>';
        $footer = '</html>';
        
        return $header.$body.$footer;
    }
    
    /**
     * htmFormular
     *  input elements with different content if logged in
     *
     * @return string
     */
    Private function htmFormular(){
        if(!isset($this->req['aktion'])){
            $aktion=$this->Felder['aktion']['standardwert'];
        }else{
            $aktion=$this->req['aktion'];
        }

        $felder = explode(",",$this->Aktionen[$aktion]['felder']);
        $protocoll = 'localhost' == $_SERVER['SERVER_NAME'] ? 'http://' : 'https://';
        $URL = $protocoll . $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
        $formularKopf = "\n<form action='".$URL."' id='".$this->Form['name']."' name='".$this->Form['name']."' enctype='multipart/form-data' method='post' enctype='multipart/form-data' method='post' accept-charset='".$this->Form['charset']."'> ";

        $isLoggedIn = $this->loginTest();
        if( $isLoggedIn <= 0 ){
                $formularBody = $this->formFeldRow('pwd');
                $formularBody .= "\n<tr>\n<td colspan='2'>";
                if( $isLoggedIn < 0 ) $formularBody.= '<label for="pwd">Passwort falsch! </label>';
                $formularBody.= "\n</td>\n</tr>";
                $formularBody.= "\n<tr><td></td><td><input type='submit' name='login' value='Login'></td></tr>";

        }else{
                $formularBody = "\n<tr>\n<td>\n\t<label title='aktion' for='aktion'>";
                $formularBody.= "".$this->Felder['aktion']['lab']."</label>\n</td>\n<td>";
                $formularBody.= "".$this->formFeldObj('aktion')."";
                $formularBody.= "\n<input type='submit' name='chng' value='Wechseln'>";
                $formularBody.= "\n</td>";
                $formularBody.= "\n</tr>";
                $formularBody.="\n<tr>\n<th align='left' colspan='2'><h2>";
                $formularBody.= $this->Aktionen[ $aktion ]['titel'];
                $formularBody.="</h2>\n</td>\n</tr>";
                foreach( $felder as $fld){
                    $formularBody.=$this->formFeldRow($fld);
                }
                $formularBody.="\n<tr><td></td><td><input type='submit' name='ok' value='Ok'></td></tr>";

        }
        $formularEnde = $this->formHidden($aktion);
        $formularEnde .= "\n</form>";

        return $formularKopf."\n<table border='0'>".$formularBody."\n</table>".$formularEnde;
    }
    
    /**
     * formHidden
     *  puts unused fields in hidden-Fields to remember variables
     *
     * @param string $aktion
     * @return string
     */
    Private function formHidden($aktion){
        $felder = explode( "," , $this->Aktionen[$aktion]['felder'] );
        $noFld=['aktion'];
        $hid = [];
        foreach(array_keys($this->Felder) as $hf){
                if( $hf == $felder[array_search( $hf , $felder)] )continue;
                if( $hf == $noFld[array_search( $hf , $noFld)] )continue;
                $hid[]="<input type='hidden' name='".$hf."' value='". ( isset($this->req[$hf]) ? $this->req[$hf] : '' ) ."'>";
        }
        $strHidden = @implode("\n\t",$hid);
        return $strHidden;
    }
    
    /**
     * formFeldRow
     *   outputs a complete table-row for input-elemnent including label
     *
     * @param string $fld
     * @return string
     */
    Private function formFeldRow( $fld ){
        if( $fld == 'pwd' && $this->loginTest() > 0 ){
            $row = "<tr><td></td><td>".$this->formFeldObj($fld)."</td></tr>";
        }else{
            if(isset($this->Felder[$fld]['lab'])){
                    $lab = "\n\t<label title='".$fld."' for='".$fld."'>".$this->Felder[$fld]['lab']."</label>";
            }else{
                    $lab = "\n\t<label title='".$fld."' for='".$fld."'>".$fld."</label>";
            }
            $row = "\n<tr>\n<td width='120'>".$lab."\n</td>\n<td>".$this->formFeldObj($fld)."\n</td>\n</tr>";
        }
        return $row;
    }
    
    /**
     * formFeldObj
     *   returns a input-element formatted as given in settings Felder
     *
     * @param string $fld
     * @return string
     */
    Private function formFeldObj( $fld ){
        $opts = '';
        switch($this->Felder[$fld]['typ']){
        case "select":
            $FldListe= $this->formFeldListCnt($this->Felder[$fld]['listen']);
            if(!is_array($FldListe))return $this->Pfade['original'];
            if(!isset($this->req[$fld])){if(isset($this->Felder[$fld]['standardwert']) ) $isSel[$this->Felder[$fld]['standardwert'] ]=" selected";}else{$isSel[ $this->req[$fld] ]=" selected";}
            foreach(array_keys($FldListe) as $oNr){ if( isset($FldListe[$oNr]) ) $opts.="\n\t\t<option value='".$oNr."'".( isset($isSel[$oNr]) ? $isSel[$oNr] : '' ).">".$FldListe[$oNr]."</option>";}
            $tiptext = isset($this->Felder[$fld]['tiptext']) ? ' ' . $this->Felder[$fld]['tiptext'] . '' : '' ;
            return "\n\t<select name='".$fld."' id='".$fld."'>".$opts."\n\t</select>".$tiptext."";
        break;
        case "password":
            $loggedIn = $this->loginTest();
            if(!isset($this->req[$fld])){
                    $defValue = isset($this->Felder[$fld]['standardwert']) ? $this->Felder[$fld]['standardwert'] : '';
            }else{
                    $defValue = $loggedIn == 1 ? $this->req[$fld] : ''; // protect from attack by " /> <input value="...
            }
            $entry = "\n\t";
            if( $loggedIn == 1 ){
                $entry.= '<input type="hidden" name="'.$fld.'" id="'.$fld.'" value="'.$defValue.'">';
            }else{
                $entry.= '<input size="40" type="text" title="'.$defValue.'" name="'.$fld.'" id="'.$fld.'" value=" ">';
            }
            $entry.= isset($this->Felder[$fld]['tiptext']) ? $this->Felder[$fld]['tiptext'] : '' ;
            
            return $entry;
        break;
        case "label":
            $entry = isset($this->Felder[$fld]['text']) ? $this->Felder[$fld]['text'] : '';
            $entry.= isset($this->Felder[$fld]['tiptext']) ? $this->Felder[$fld]['tiptext'] : '' ;
            return $entry;
        break;
        case "text":
        default:
            if(!isset($this->req[$fld])){$defValue = isset($this->Felder[$fld]['standardwert']) ? $this->Felder[$fld]['standardwert'] : '';}else{$defValue = $this->req[$fld];}
            return "\n\t<input size='50' type='text' name='".$fld."' id='".$fld."' value='".$defValue."'>" . ( isset($this->Felder[$fld]['tiptext']) ? $this->Felder[$fld]['tiptext'] : ''  );
        break;
        }
    }
    
    /**
     * formFeldListCnt
     *  Returns lists as array for different purposes.
     *  Used to fill select-options in select-elements.
     *
     * @param string $lstNam
     * @return array
     */
    Private function formFeldListCnt( $lstNam ){
        $outArr = [];
        switch($lstNam){
        case "aktList":
            foreach(array_keys($this->Aktionen) as $akt){
                $outArr[$akt]=$this->Aktionen[$akt]['titel'];
            }
            return $outArr;
        break;
        case "fileList":
            $rootpfad=$this->Pfade['original'];
            $Fils = $this->formFeldListZeigPfad( $rootpfad );
            foreach($Fils as $fil){
                if(filetype($rootpfad.$fil)!='file') continue;
                $mime = pathinfo($fil,PATHINFO_EXTENSION);
                if( !isset( $this->mim[$mime]['cmd'] ) ) continue;
                $outArr[$fil]=$fil;
            }
            return $outArr;
        break;
            case "subDirList":
                $rootpfad=$this->Pfade['original'];
                $Fils = $this->formFeldListZeigPfad( $rootpfad );
                $actalPath = trim(str_replace( $this->Pfade['basis'] , '' , $this->Pfade['original']) , '/');
                $outArr['.'] = $actalPath . '/ (aktueller Scriptpfad)';
                if(is_array($Fils)){
                        sort($Fils);
                        foreach($Fils as $fil){
                                if(filetype($rootpfad.$fil)!='dir'){continue;}
                                $outArr[$actalPath . '/' . $fil] = $actalPath . '/' . $fil . '';
                        }
                }
                return $outArr;
            case "dirList":
                $rootpfad=$this->Pfade['basis'];
                $Fils = $this->formFeldListZeigPfad( $rootpfad );
                if(is_array($Fils)){
                        sort($Fils);
                        foreach($Fils as $fil){
                                if(substr($fil,0,4)=='_vti'){continue;}
                                if($fil=='_private'){continue;}
                                if($fil=='cgi-bin'){continue;}
                                if($fil==$outArr['.']){continue;}
                                if(filetype($rootpfad.$fil)!='dir'){continue;}
                                $outArr[$fil] = $fil ;
                        }
                }
                return $outArr;
            break;
        }
    }
    
    /**
     * Helper formFeldListZeigPfad
     *  helper for formFeldListCnt()
     *  returns an array conatining the file-list from given path
     *
     * @param string $zPfad
     * @return array
     */
    Private function formFeldListZeigPfad( $zPfad ) {
            $aFiles = [];
            if(!file_exists($zPfad)) return false;
            $verz0 = opendir ( $zPfad );
            while ( $file = readdir ( $verz0 ) ){
                if ($file !="." && $file !="..") $aFiles[]= $file;
            };
            closedir ( $verz0 );
            return $aFiles;
    }
    
    /**
     * loginTest
     *  returns 
     *   0 if not logged in AND NO login-button clicked
     *   1 if logged in
     *  -1 if login failed
     *
     * @return int
     */
    Private function loginTest(){
//         if( 'da39a3ee5e6b4b0d3255bfef95601890afd80709' == $this->strDocupassword ) return 1;
        if( !isset($this->req['pwd']) ) return 0;
        if( $this->req['pwd'] && sha1($this->req['pwd']) == $this->strDocupassword ) return 1;
        return -1;
    }
    
    /**
     * runAction
     *  detect and run an action
     *
     * @return string with result for debug-purpose
     */
    Private function runAction(){
        if(!isset($this->req['aktion'])){$act=$this->Felder['aktion']['standardwert'];}else{$act=$this->req['aktion'];}
        if( $this->loginTest() < 0 ){return 'Passwort fehler';}
        
        $action = $this->Aktionen[$act]['script'];
        if( method_exists( $this , $action ) ) $workResult = $this->$action();
        if( !isset($workResult) ) return "Aktion ".$this->Aktionen[$act]['titel']. ": nicht gelungen ";
        
        return "Aktion <i>".$this->Aktionen[$act]['titel']."</i>".trim(" ".$workResult);
        
    }
    
    /**
     * Action actUnzip
     *
     * @return string with result for debug-purpose
     */
    Private function actUnzip(){
        //if(empty($this->req['neupfad']))return "nicht m&ouml;glich ohne umbenennen! (".$this->Felder['neupfad']['lab'].")";
        if ( !isset( $this->Pfade['original'] ) ) return '. »Pfad nicht gefunden«';
        if ( !isset( $this->req['original'] ) ) return '. »Keine passende Original-Datei vorhanden«';
        
        $ext = pathinfo( $this->Pfade['original'].$this->req['original'],PATHINFO_EXTENSION);
        
        if($this->req['subpfad']=='.'){//  hier entpackt 
            $rootpath= $this->Pfade['original']; 
        }elseif($this->req['subpfad']=='/'){
            $rootpath = $this->Pfade['basis'];
        }else{
            $rootpath = $this->Pfade['basis'].ltrim($this->req['subpfad'],"/")."/" ;
        }
        if (!file_exists($rootpath)) return '. Fehler - Pfad nicht gefunden: ' . $rootpath . ' '; //{mkdir($rootpath);}
        
        $command = $this->mim[$ext]['cmd']." ".$this->Pfade['original'].$this->req['original']." " . $this->mim[$ext]['opt'] . " ".$rootpath."";
        
        $op = exec( $command , $aExecResult);
        if(count($aExecResult) > 1){
            $outText =  ". Antwort: ". count($aExecResult) . " Dateien entpackt nach " . $rootpath . ".";
        }else{
            $outText = ". Keine Aktion, vielleicht aufgrund eines existenten Verzeichnisses? <br />Antwort: [" . ( isset($aExecResult[0]) ? $aExecResult[0] : "0" ) . "]";
        }
        
        return  $outText;
    }
    
    /**
     * Action actLink
     *
     * @return string with result for debug-purpose
     */
    Private function actLink(){
        if( !isset($this->req['linkdatei']) || empty($this->req['linkdatei']) ){
            return ". Fehler: 'Linkdatei' darf nicht leer sein.";
        }elseif( !isset($this->req['symlink']) || empty($this->req['symlink']) ){
            return ". Fehler: 'Symlink' darf nicht leer sein.";
        }elseif( strpos( ' ' . $this->req['symlink'] , '..' ) ){
            return ". Fehler: 'Symlink' darf nur im aktuellen Pfad oder einem Unterpfad erstellt werden. <br />Symlink Enth&auml;lt &raquo;..&laquo;!";
        }

        $link = trim( $this->req['symlink'] , '/' );
        // if the new link is in a deeper directory then prepend '../'
         $aLink = explode( '/' , $link );
         $relatedOrignPath = $this->req['linkdatei'];
        if( count($aLink) > 1 ){ 
                $relatedOrignPath =  str_repeat( '../' , count($aLink)-1 ) . $this->req['linkdatei'];
        }
        
        $aShrinkBase = explode( '/' , trim( dirname(__FILE__) , '/' ) );
        
        $aOrig = explode( '/' , $this->req['linkdatei'] );
        $aTempBaseDir = [];
        foreach( $aOrig as $pathPart ){
            if( $pathPart == '..' ){ 
                $aTempBaseDir[] = array_pop($aShrinkBase) ; 
            }else{
                $aTempBaseDir[] = $pathPart ; 
            }
        }
        $lastIdx = count( $aTempBaseDir );
        $tempShrDir = implode( '/' , $aShrinkBase );
        
        $strPartName = '';
        foreach( $aOrig as $pathPart ){
            $lastIdx -= 1;
            if( $pathPart == '..' ){ 
                $tBaseDir = $aTempBaseDir[$lastIdx] ;
            }else{
                $strPartName .= '/' . $pathPart ; 
            }
        }
        $pathToOrigin = '/' . rtrim( $tempShrDir , '/' ) .''. $strPartName;
        
        $tempBaseDir = '/' . trim( dirname(__FILE__) , '/' ) . '/' ;
        
        if(!file_exists($pathToOrigin) ){
            return ". Fehler: 'linkdatei' existiert nicht: ".$pathToOrigin;
        }elseif(!file_exists(dirname($tempBaseDir.$link)) ){
            return ". Fehler: Directory f&uuml;r 'symlink'  existiert nicht: " . dirname($tempBaseDir.$link);
        }elseif( file_exists($tempBaseDir.$link) || is_link($tempBaseDir.$link) ){
            return ". Fehler: Datei existiert (".filetype($tempBaseDir.$link)."): ".$tempBaseDir.$link."";
        }
        
        symlink( $relatedOrignPath ,  $link);
        // also possible by exec:
        // exec( 'ln -s ' . $this->req['linkdatei'] . ' ' . $link );
        return ". ok, gelinkt: ".$tempBaseDir.$link." <br />-> Verweist auf: ".$relatedOrignPath."";
    }
    
    /**
     * Action actDeletelink
     *
     * @return string with result for debug-purpose
     */
    Private function actDeletelink(){
        if( isset($this->Felder['symlink']['standardwert']) ) unset($this->Felder['symlink']['standardwert']);
        
        if( !isset($this->req['symlink']) || empty($this->req['symlink']) ){
            return ". Fehler: 'Symlink' darf nicht leer sein.";
        }
        
        $link = trim($this->req['symlink'],"/");
        $pathToOrigin = '/' . trim( dirname(__FILE__) , '/' ) . '/';
        if(!file_exists($pathToOrigin.$link) ){
            return ". Fehler: Symlink '".$pathToOrigin.$link."' <br />existiert nicht.";
        }elseif(filetype($pathToOrigin.$link) != 'link' ){
            return ". Fehler, Datei '".$pathToOrigin.$link."' <br />ist kein Link, sondern vom Typ '".filetype($pathToOrigin.$link)."'.";
        }
        
        unlink($pathToOrigin.$link);
        
        return ". ok, Link gel&ouml;scht: ".$pathToOrigin.$link;
    }
    
    /**
     * Action actFileInfo
     *
     * @return string with result for debug-purpose
     */
    Private function actFileInfo(){
        $aOptions = [ 
                'ordner'  => [
                    'style' => 'color:#fff;' ,                   
                    'command' => 'Ziel f&uuml;r Symlink oder zum entpacken'
                ], 
                'aktuell' => [ 
                    'style' => 'font-style:italic;' ,            
                    'command' => '' 
                ], 
                'link'    => [ 
                    'style' => 'color:#eb0;' ,                   
                    'command' => 'Symlink l&ouml;schen' 
                ], 
                'datei'   => [ 
                    'style' => 'color:#090;' ,                   
                    'command' => '' 
                ], 
                'aktion'  => [ 
                    'style' => 'color:#c70;font-style:italic;' , 
                    'command' => '' 
                ]
        ];
        
        $longest = 0;
        $aFile = [];
        $aFilesInPath = $this->formFeldListZeigPfad( $this->Pfade['original'] );
        foreach( $aFilesInPath as $filename ){
            if( is_link($this->Pfade['original'] .$filename) ){
                $typ = 'link';
                
            }elseif( $this->Pfade['original'] .$filename == __FILE__ ){
                $typ = 'aktuell';
                
            }elseif( is_dir( $this->Pfade['original'] .$filename) ){
                $typ = 'ordner';
                $filename .= '/';
                
            }else{
                $typ = 'datei';
                
            }
            $aFile[$typ=='ordner'?0:1][$filename] = $typ;
            if( strlen($filename) > $longest ) $longest = strlen($filename);
        }
        // add 3 points as offset, sort to set order for dir and file
        $longest +=3;
        ksort($aFile);
        
        $fileInfo = '<div style="padding:3px;font-size:11pt;font-family: courier,monospace;background:black;color:#e0e0e0;">';
        $fileInfo .= '<i>Dateien in diesem Pfad, [typ] und <span style="' . $aOptions['aktion']['style'] . '">Aktionen, die mit diesem Script ausgef&uuml;hrt werden k&ouml;nnen:</span> </i>';
        $fileInfo .= '<p style="padding:3px;font-family: courier,monospace;background:black;">';
        
        foreach( $aFile as $srt => $aSrtFile ) { 
            if( is_array($aSrtFile) ) ksort($aSrtFile);
            foreach( $aSrtFile as $file => $typ ) { 
                    $mim = pathinfo( $file , PATHINFO_EXTENSION ) ;
                    $aOptions['datei']['command'] = isset($this->mim[$mim]['cmd']) ? 'Datei entpacken mit &raquo;' . $this->mim[$mim]['cmd'] . '&laquo;' : '';
                    $strHintOffset = strlen('aktuell') - strlen($typ) ;
                    $strTabOffset = $longest - strlen( $file );
                    $fileInfo .= '<span style="'; 
                    $fileInfo .= $aOptions[$typ]['style']; 
                    if($aOptions[$typ]['command']) $fileInfo .= 'font-weight:bold;'; 
                    $fileInfo .= '">'; 
                    $fileInfo .= $file; 
                    if( $strTabOffset ) $fileInfo .= str_repeat( '.' , $strTabOffset ); 
                    $fileInfo .= '['. trim( ucFirst($typ) ) . ']</span>'; 
                    if($aOptions[$typ]['command'] && $strHintOffset ) $fileInfo .= ' <span style="' . $aOptions['aktion']['style'] . '"> ' . str_repeat( '&nbsp;' , $strHintOffset ) . $aOptions[$typ]['command'] . '</span>'; 
                    $fileInfo .= '<br />';
            }
        }
        $fileInfo .= '</p>';
        $fileInfo .= '<i>Total '. count($aFile[0]) . ' Ordner und '. count($aFile[1]) . ' Dateien.</i>';
        $fileInfo .= '</div>';
        return $fileInfo;
    }
    
    /**
     * Action actPreauth
     *
     * @return string with result for debug-purpose
     */
    Private function actPreauth(){
        if( !isset($this->req['username']) || empty($this->req['username']) || trim($this->req['username']) == '' ) return '. Kein Benutzername angegeben.';
        if( !isset($this->req['subdomains']) ) return '. Kein Url angegeben.';
        
        if( empty( $this->aIngredients['timestamp'] ) ) {
            $this->aIngredients['timestamp'] = ( time() * 1000 );
        }
        
        $this->aIngredients['account'] = $this->req['username'];
        
        $aXingedients = $this->aIngredients;
        unset($aXingedients['preauth']);
        $xDataString = implode( $this->strAuthSeparer , $aXingedients );
        $this->aIngredients['preauth'] = hash_hmac('sha1', $xDataString , $this->strSecretPreauthKey );

        $uri = '';
        $z = 0;
        foreach( $this->aIngredients as $key => $value ){
            ++$z;
            $uri .= 1==$z ? '?' : '&amp;' ;
            $uri .= $key . '=' . $value;
        }
        
        $timeInfo = 'g&uuml;ltig bis um ' . date( 'H:i' , ( ($this->aIngredients['timestamp']/1000) + 600 ) )  . ' Uhr';
        $strForm = ':  <p>Links f&uuml;r &laquo;<b>'.$this->req['username'].'</b>&raquo;, '.$timeInfo.'</p>';
        $subdomains = strpos( $this->req['subdomains'] , ',') ? explode( ',' , $this->req['subdomains'] ) : explode( ' ' , $this->req['subdomains'] ) ;
        foreach( $subdomains as $subdom ) $strForm .= '<a target="_blank" href="' . trim(trim($subdom)) . '/apps/Login.aspx' . $uri . '">' . trim($subdom) . '</a> <br />';

        return " " . $strForm;
        
    }
    
    /**
     * Action actPassword
     *
     * @return string with result for debug-purpose
     */
    Private function actPassword(){
        $passwortIn = isset($this->req['passwort']) ? $this->req['passwort'] : '';
        $outtext = '<p><label for="code">Private $strDocupassword =</label> <input type="text" size="41" id="code" style="padding:4px;font-weight:bold;background:black;color:white;font-family:monospace;font-size:11pt;" value="' . sha1($passwortIn) . '" /> <i>Verschl&uuml;sselter hash</i></p>';
        $outtext .= '<p><label for="code">Kopieren und im Script einf&uuml;gen.</label> </p>';
        return $outtext;
    }
    
}

error_reporting(-1);

$frm = new user_install();
echo $frm->start_user_install();

die();

?>

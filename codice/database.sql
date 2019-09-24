# 
# HackerLab
# Filippo finke
# 
# Creazione database
# 
DROP DATABASE IF EXISTS hackerlab;
CREATE DATABASE hackerlab;
USE hackerlab;

#
# Creazione tabelle
# 

# Tabella permessi
CREATE TABLE permissions(
    name VARCHAR(30) PRIMARY KEY
);

# Tabella utenti
CREATE TABLE users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    permission VARCHAR(30),
    full_name VARCHAR(30) NOT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    enabled TINYINT(1) DEFAULT 1,
    UNIQUE(email),
    FOREIGN KEY(permission) REFERENCES permissions(name) ON UPDATE CASCADE
);

# Tabella articoli
CREATE TABLE articles(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    content TEXT(2000) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);

# Tabella commenti
CREATE TABLE comments(
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    user_id INT,
    comment VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE

);

#
# Inserimento dati
#

# Permessi predefiniti
INSERT INTO permissions VALUES('user');
INSERT INTO permissions VALUES('administrator');

# Utenti predefiniti
INSERT INTO users VALUES(null,'admin@hackerlab.ch', '$2y$10$GBiarCslujuL/bqweq9HXOuunR4c/GAIebIfmEQ1F41JCeJyLLAYO', 'administrator', 'Administrator', NULL, 1); # Password: PasswordSegreta
INSERT INTO users VALUES(null,'filippo.finke@samtrevano.ch', '$2y$10$vh5dNYlbNzhOuuq4GjtZ2.Vl6xTYq94yxeJWfbGYGalNoNiOMEdUS', 'user', 'Filippo Finke', NULL, 1); # Password: 1234

# Articoli predefiniti
INSERT INTO articles VALUES(null, 1, 'Broken Authentication', NULL, 'Questi tipi di vulnerabilità possono consentire a un aggressore di catturare o bypassare i metodi di autenticazione utilizzati da un\'applicazione web. <br> <br> All\'interno di HackerLab è presente una vulnerabilità di questo tipo. <br> Per sfruttare questa vulnerabilità basterà utilizzare una comune estensione come per esempio: <a target="_blank" href="https://chrome.google.com/webstore/detail/editthiscookie/fngmhnnpilhplaeedifhccceomclgfbg?hl=it">EditThisCookie</a> <br> Una volta installata l\'estensione sarà possibile modificare i cookie all\'interno dei siti web. <br> Noterai che HackerLab ha un cookie chiamato <code>permission</code>, il contenuto di questo cookie è famigliare, è un testo codificato in base64. <br> <br> <code>dXNlcg== -> user</code> <br> <br> Con un po\' di fortuna è possibile indovinare quale sarà il permesso di un amministratore, quindi provando per esempio a modificare il cookie in <br> <br> <code>YWRtaW5pc3RyYXRvcg== -> administrator</code> <br> <br> e ricaricando la pagina potrai notare delle modifiche nel layout, ora potrai vedere informazioni e pagine aggiuntive come se fossi un amministratore! <br><br> Mmm, sono curioso di come potresti sfruttare questa vulnerabilità...<br><a target="_blank" href="https://hdivsecurity.com/owasp-broken-authentication-and-session-management#targetText=What%20is%20Broken%20authentication%20and,are%20not%20protected%20when%20stored.&targetText=Session%20IDs%20are%20vulnerable%20to%20session%20fixation%20attacks.">Fonti</a>', CURRENT_TIMESTAMP);
INSERT INTO articles VALUES(null, 1, 'File inclusion vulnerability o Directory Traversal', '07085c4c09e694c7c5ab8093d1aa0eb3d22715e881d4d9f5ab8a7f69864c31d7', 'Una directory traversal (o path traversal) consiste nello sfruttare l\'insufficiente validazione della sicurezza / sanificazione dei nomi dei file di input forniti dall\'utente, in modo che i caratteri che rappresentano "traverse to parent directory" siano passati attraverso le API dei file. <br> <br> Grazie a questa descrizione potresti già aver individuato un percorso vulnerabile a questo tipo di attacco, se non lo hai trovato un indizio potrebbe essere l\'immagine di questo articolo. <br> Il percorso tramite il quale vengono ricavate le immagini in HackerLab è il seguente <br> <br> <code>/image/?file_name=IMAGE</code> <br> <br> Bene, per eseguire questa vulnerabilità basterà sostituire il valore del nome dell\'immagine con dei file conosciuti, potremmo per esempio iniziare tentando di capire la struttura del programma provando svariati file: <ul> <li>.htaccess</li> <li>../composer.json</li> <li>e così via </li> </ul> Possiamo notare come nel caso di <code>/image/?file_name=../composer.json</code> abbiamo ricevuto una risposta: <br> <code> {<br> "name": "filippofinke/HackerLab",<br> "description": "Sito web per la dimostrazione di vulnerabilità",<br> "authors": [<br> {<br> "name": "Filippo Finke"<br> }<br> ],<br> "require": {<br> "php": ">=5.6",<br> "slim/php-view": "^2.0",<br> "slim/slim": "^3.1",<br> "phpmailer/phpmailer": "^6.0"<br> },<br> "scripts": {<br> "start": "sudo php -S 127.0.0.1:80 -t public"<br> }<br> }<br> </code> <br> <br> Attraverso questa risposta possiamo confermare la presenza della vulnerabilità. <br> <br> Ci saranno altri file accessibili? <br> <a target="_blank" href="https://en.wikipedia.org/wiki/Directory_traversal_attack">Fonti</a>', CURRENT_TIMESTAMP);
INSERT INTO articles VALUES(null, 1, 'Insecure Direct Object References', NULL, 'I riferimenti diretti agli oggetti insicuri si verificano quando un\'applicazione fornisce l\'accesso diretto agli oggetti in base all\'input fornito dall\'utente. Come risultato di questa vulnerabilità gli aggressori possono aggirare l\'autorizzazione e accedere direttamente alle risorse del sistema, ad esempio i record o i file del database. <br> <br> In base a questa piccola descrizione forse avrai già riconosciuto questa vulnerabilità all\'interno di HackerLab. <br> Se presti attenzione alla pagina di questo post noterai che il percorso per arrivarci è <code>/post/3</code> <br> Quindi possiamo considerare il percorso come <code>/post/POST_ID</code> <br> Questo conferma dunque la presenza di questa vulnerabilità. <br> <br> Chissà cosa può comportare questa vulnerabilità... <br> Quando navighi presta attenzione :D <br> <a target="_blank" href="https://www.owasp.org/index.php/Testing_for_Insecure_Direct_Object_References_(OTG-AUTHZ-004)#targetText=Insecure%20Direct%20Object%20References%20occur,example%20database%20records%20or%20files.">Fonti</a>', CURRENT_TIMESTAMP);
INSERT INTO articles VALUES(null, 1, 'Account takeover vulnerability', NULL, 'Una vulnerabilità di tipo "Account takeover vulnerability" è quando un attaccante riesce a prendere il controllo completo dell\'account di un altra persona registrata ad una determinata piattaforma. <br> <br> Anche questa vulnerabilità è presente in HackerLab. <br> Questa vulnerabilità è più difficile da identificare, per accedere ad HackerLab si dispongono di solamente una opzione, ovvero di accedere con email e password. <br> Se hai prestato attenzione a ciò che ho scritto precedentemente ti sarai soffermato sui parametri <code>email e password</code>, perfetto. Analizzando bene i due parametri possiamo dire che il parametro <code>email</code> non è possibile da attaccare in quanto non è possibile eseguirne una modifica, mentre in HackerLab è presente una funzionalità di recupero password che può modificare il parametro <code>password</code>. <br> Bene, abbiamo trovato cosa testare per rilevare se è presente una vulnerabilità di questo tipo. <br> <br> Richiedendo una email di recupero password possiamo notare che il contenuto dell\'email è simile al seguente: <br> <code> Recupera la tua password premendo il seguente link:<br> http://hackerlab.ch/?reset_token=MTU2ODk4ODU2OA%3D%3D </code> <br> Possiamo notare un parametro, <code>reset_token</code>, che andremo ad attaccare. <br> Se si analizza più attentamente il parametro possiamo notare che è una codifica in base64, andandola a decodificare otteniamo:<br> <code>156898856</code> <br> A primo impatto può sembrare un numero casuale, ma provando ad inviare più email di recupero possiamo notare che continua ad incrementare con una logica, ovvero quella del tempo. <br> Il token di recupero è quindi la codifica in base64 del tempo di quando è stato richiesto il recupero. <br> Possiamo fare una bozza del codice: <br> <code>$token = base64_encode(time());</code> <br> <br> Questo è tutto, chissà come potrai sfruttarla...', CURRENT_TIMESTAMP);

# Commenti predefiniti
INSERT INTO comments VALUES(null, 1, 1, 'Potresti guardare il mio profilo, non so :)', CURRENT_TIMESTAMP);
INSERT INTO comments VALUES(null, 2, 2, 'Sono sicuro che usate git!', CURRENT_TIMESTAMP);
INSERT INTO comments VALUES(null, 3, 2, 'Penso che questa vulnerabilità sia solo nel percorso /post ...', CURRENT_TIMESTAMP);
INSERT INTO comments VALUES(null, 4, 2, 'In questo caso è inutile, come faccio a sapere le email degli altri utenti...', CURRENT_TIMESTAMP);

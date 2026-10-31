# Lumèa

Applicazione web per la gestione di un salone di parrucchieri, sviluppata per il progetto universitario di Programmazione Web. Collega la prenotazione dei clienti alla gestione quotidiana del proprietario.

**Versione online:** [lumea.infinityfree.io](https://lumea.infinityfree.io)

## Funzionalità

**Cliente:** registrazione e accesso, catalogo Uomo/Donna, scelta di servizio e disponibilità, prenotazione guidata, storico e annullamento secondo le regole applicative, lista d’attesa, coupon fedeltà e riepilogo delle spese. La disponibilità tiene conto della durata del servizio, degli appuntamenti esistenti e degli slot dinamici; viene ricontrollata alla conferma.

**Proprietario:** dashboard, agenda giornaliera e prossime sette giornate di apertura, gestione dei servizi e degli orari, eccezioni e chiusure, elenco clienti con ricerca e ordinamento, registrazione dei pagamenti con eventuale coupon, assenze e riabilitazione delle prenotazioni online, statistiche.

La lista d’attesa valuta le richieste in ordine FIFO e assegna il primo richiedente ancora idoneo quando lo slot diventa disponibile. Il credito del coupon viene utilizzato al pagamento in salone, non durante la prenotazione. Le cinque notifiche riguardano conferma, assegnazione dalla lista d’attesa, premio coupon, sospensione delle prenotazioni online e annullamento da parte del salone; i flussi le richiedono dopo il commit delle operazioni pertinenti.

## Tecnologie e architettura

PHP, MySQL/MariaDB con PDO, Smarty 4.5.8 incluso nel repository, HTML, CSS e JavaScript senza dipendenze di build frontend. Il server Apache usa `.htaccess` e `mod_rewrite`.

La separazione **PCEF** è realizzata come segue:

| Livello | Directory | Responsabilità |
|---|---|---|
| Presentation | `View/` e template Smarty | Preparazione della risposta, rendering HTML e header di presentazione |
| Control | `Control/` | Casi d’uso, autorizzazioni e coordinamento delle operazioni |
| Entity | `Entity/` | Oggetti del dominio e relativi comportamenti |
| Foundation | `Foundation/` | Persistenza PDO, sessioni e servizi tecnici, incluso il trasporto email |

`index.php` inizializza l’applicazione e invoca `CFrontController`. Il Front Controller risolve le richieste tramite una lista chiusa di rotte e controlla la protezione CSRF prima di inoltrare i POST. Sono supportati gli URL storici `index.php?pagina=...` e le rotte autodescrittive definite nel codice. Le notifiche sono coordinate dal Control, presentate da `VEmail` e affidate alla Foundation per il trasporto.

## Struttura del repository

```text
index.php                  Ingresso applicativo
Control/                   Controller e Front Controller
Entity/                    Entità del dominio
Foundation/                Persistenza e servizi tecnici
View/                      Classi di presentazione
  smarty/libs/             Libreria Smarty inclusa
  smarty/templates/        Template delle pagine e delle notifiche
  smarty/templates_c/      Compilazioni generate da Smarty
  smarty/cache/            Directory runtime della cache
config/                    Modelli di configurazione e protezione HTTP
database/lumea.sql         Schema SQL delle otto tabelle, senza dati
css/ js/ images/           Risorse pubbliche
.htaccess                  Routing e blocchi di accesso HTTP
.user.ini                  Impostazioni PHP per CGI/FPM
```

## Installazione locale con XAMPP

### 1. Requisiti e file

- Apache 2.4 con `mod_rewrite`, permesso di usare `.htaccess` e direttive `Options`/`Require`.
- PHP 8.0 o successivo per le funzioni utilizzate; i controlli locali sono eseguiti con PHP 8.2.4. Sono necessari PDO con `pdo_mysql`, `mbstring`, sessioni, JSON, filter e OpenSSL per SMTP TLS.
- MySQL con supporto ai vincoli `CHECK` (8.0.16 o successivo), oppure MariaDB compatibile; lo schema è collaudato con MariaDB 10.4.28.
- Cartelle temporanee delle sessioni PHP e directory runtime Smarty scrivibili dal processo PHP, senza assegnare indiscriminatamente permessi `777`.

Copiare o clonare il progetto in `htdocs/Lumea`, avviare Apache e MySQL, quindi usare `http://localhost/Lumea/`. Con XAMPP sul Mac il percorso usuale è `/Applications/XAMPP/xamppfiles/htdocs/Lumea`.

Smarty è già incluso: non occorrono Composer o npm. Conservare `View/smarty/LICENSE`, la libreria e le cartelle runtime. I `.gitkeep` mantengono queste ultime nel repository; i file compilati vengono rigenerati automaticamente. Non occorre creare `View/smarty/configs` per i template attuali, che non caricano configurazioni Smarty.

### 2. Creare lo schema

In phpMyAdmin locale importare `database/lumea.sql` in un ambiente nuovo. Il file contiene `CREATE DATABASE IF NOT EXISTS lumea`, `USE lumea` e le otto tabelle:

`utenti`, `servizi`, `coupon`, `prenotazioni`, `lista_attesa`, `pagamenti`, `disponibilita`, `eccezioni_disponibilita`.

**Non importarlo su un database esistente da conservare:** non è una migrazione né un aggiornamento idempotente. Non contiene clienti, credenziali di accesso, dati demo o servizi iniziali. Su un hosting con database preassegnato, come InfinityFree, importare una copia dello schema privata omettendo le prime due istruzioni `CREATE DATABASE` e `USE`, dopo aver selezionato il database remoto corretto. Non caricare SQL nel sito pubblico.

Creare un utente MySQL dedicato al database con i permessi applicativi di lettura e scrittura. Non usare credenziali amministrative in produzione.

### 3. Configurare il database

I file `.example` sono modelli: **non vengono caricati automaticamente**. Sostituire tutti i segnaposto nelle copie private, mai nei file versionati. Non inserire password o configurazioni reali su GitHub.

**XAMPP, configurazione esterna consigliata:** creare la cartella `lumea-private` accanto al DocumentRoot `htdocs`, quindi copiare `config/database.example.json` come `lumea-private/database.json`. Sul Mac con XAMPP il percorso predefinito è `/Applications/XAMPP/xamppfiles/lumea-private/database.json`.

Compilare `host`, `name`, `user`, `password`, `port` e, se necessario, `socket`. Impostare `environment` a `local` soltanto per l’installazione locale; per un ambiente pubblico mantenere `production`. Un socket vuoto usa host e porta. Sul Mac, se si vuole la connessione TCP, usare `127.0.0.1` come host. Il file privato deve essere leggibile dall’utente PHP ma non accessibile agli altri utenti del sistema: il loader rifiuta permessi che consentano accesso alla categoria “altri”.

È possibile indicare un diverso file esterno con `LUMEA_DB_CONFIG`. Le variabili `LUMEA_DB_HOST`, `LUMEA_DB_NAME`, `LUMEA_DB_USER`, `LUMEA_DB_PASSWORD`, `LUMEA_DB_PORT`, `LUMEA_DB_SOCKET` e `LUMEA_ENV`, se definite nell’ambiente PHP, prevalgono sui valori letti dal file.

**Hosting senza configurazione esterna accessibile:** prima verificare che l’accesso HTTP a `config/` sia bloccato, anche per un file di prova innocuo e tramite le rotte riscritte. Soltanto dopo copiare `config/database.infinityfree.example.php` come `config/database.infinityfree.php` e compilare i segnaposto. Conservare il controllo iniziale del modello e `config/.htaccess`. Il loader usa questo percorso fisso solo se non trova il file esterno e non è impostato `LUMEA_DB_CONFIG`.

Non creare entrambe le configurazioni senza una ragione: il file esterno esistente ha precedenza. La semplice presenza di `.htaccess` non sostituisce la verifica del suo effettivo funzionamento sul server.

### 4. Primo accesso e dati iniziali

Lo schema vuoto non crea automaticamente un proprietario. Registrare dall’interfaccia un account destinato al gestore, con email e telefono validi, e annotarne l’ID. L’amministratore del database deve poi impostare **solo per quell’ID** il campo `ruolo` a `proprietario` nella tabella `utenti`, senza alterare `password_hash`. Eseguire logout e un nuovo login.

Non esiste una registrazione pubblica che permetta di scegliere il ruolo proprietario. Prima di utilizzare prenotazioni e calendario occorre configurare questo account: il blocco transazionale del calendario ne richiede la presenza. Dal pannello proprietario aggiungere servizi e orari di apertura; registrare separatamente gli account cliente necessari. Le password passano attraverso la normale registrazione e vengono salvate hashate. I dati dimostrativi dell’installazione d’esame non sono inclusi nello schema del repository.

### 5. Configurare le email

Senza una configurazione email disponibile il trasporto è `disabled`. Per provare il progetto senza invii reali, lasciare l’email disabilitata oppure usare la modalità `log` con un percorso privato esterno al DocumentRoot.

- **XAMPP:** copiare `config/email.example.json` in `lumea-private/email.json`, nello stesso percorso esterno usato per la configurazione database. Configurare un `log_path` assoluto, privato e scrivibile. Il modello usa `mode: log` e `production_enabled: false`. Non contiene credenziali valide. Si può scegliere un altro file esterno con `LUMEA_EMAIL_CONFIG`; `LUMEA_EMAIL_MODE`, se definita, prevale sulla modalità del file.
- **Hosting:** dopo aver verificato il blocco HTTP di `config/`, copiare `config/email.infinityfree.example.php` come `config/email.infinityfree.php`, conservandone la protezione iniziale. La modalità iniziale è `disabled`. Non abilitare gli invii per il solo fatto di aver copiato il modello.

Per SMTP compilare host, porta, username, password e mittente autorizzato. Il trasporto usa autenticazione e `starttls` oppure `tls`, verificando i certificati; non usa PHP `mail()`. Sono necessari connettività SMTP in uscita e certificati CA corretti sull’ambiente di installazione. I segnaposto SMTP e il destinatario di prova vanno sostituiti con valori effettivi prima di attivare una modalità che li utilizzi.

La modalità `smtp_test` ridirige verso un solo `test_recipient`, verifica il database autorizzato e può essere limitata al collaudo manuale: non abilita da sola gli invii ordinari. Le normali notifiche SMTP richiedono esplicitamente `mode: smtp` e `production_enabled: true`. Gli indirizzi dimostrativi con dominio `.test` non sono destinatari SMTP ammessi. `log` registra i messaggi su file: proteggerlo come dato privato, non pubblicarlo né versionarlo. Questa installazione non richiede di effettuare invii reali.

## Sessioni e protezioni implementate

- Sessioni PHP con modalità strict, rigenerazione dell’ID e del token CSRF al login; logout con distruzione della sessione e scadenza del cookie.
- Cookie `HttpOnly`, `SameSite=Lax` e `Secure` quando la richiesta è HTTPS. Autenticazione con scadenza di due ore per il cliente e dodici ore per il proprietario.
- Token CSRF di sessione generati con `random_bytes()` e verificati con `hash_equals()` prima dei POST gestiti dal Front Controller.
- Password gestite con `password_hash()` e `password_verify()`; controllo dei ruoli e dell’accesso alle risorse.
- Accesso SQL con PDO e prepared statement per i parametri; transazioni e blocco del calendario nei flussi che lo richiedono.
- Validazione lato server, incluso il controllo del dominio email; JavaScript migliora il feedback senza sostituire i controlli PHP. Il DNS non dimostra il possesso della casella.
- Template con escaping dei valori nei punti previsti e regole Apache che bloccano accesso diretto a classi, template, database, configurazioni e file riservati.
- Errori tecnici non esposti all’utente e configurazioni private escluse da Git.

Le regole Apache vanno collaudate sul server effettivo. `.user.ini` si applica a CGI/FPM; con Apache mod_php occorre impostare gli equivalenti nella configurazione PHP. In produzione utilizzare HTTPS. Non pubblicare `.git`, backup, SQL privati, log, file di sessione o cache contenenti dati dell’installazione.

## Verifica di una nuova installazione

1. Verificare che esistano tutte le otto tabelle e che il collegamento PDO usi il database desiderato.
2. Aprire home, registrazione e login; verificare CSS, JavaScript e favicon.
3. Controllare accesso e logout dei due ruoli, poi configurare catalogo e orari usando soltanto dati di prova.
4. Provare una prenotazione futura, la sua visualizzazione nell’agenda e le azioni consentite, in un database di prova.
5. Verificare che una richiesta POST priva di token CSRF venga rifiutata e che le directory riservate restituiscano 403/404.
6. Verificare le nuove rotte, per esempio `/sessione`, anche nella sottocartella `/Lumea`.
7. Conservare le email in modalità disabilitata o log fino a un collaudo esplicitamente autorizzato.

Non usare il database dell’esame o quello online per prove che inseriscono, modificano o eliminano dati. Questo repository non include una suite automatica di test né le configurazioni private dei collaudi.

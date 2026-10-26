<?php

require_once __DIR__ . '/smarty/libs/Smarty.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';

class VBase extends Smarty
{
    public function __construct()
    {
        parent::__construct();

        $baseDirSmarty = __DIR__ . '/smarty';

        // Directory contenente i template .tpl
        $this->setTemplateDir($baseDirSmarty . '/templates');

        // In produzione usa la cartella del progetto; i test possono indicarne una isolata.
        $compileDir = getenv('LUMEA_SMARTY_COMPILE_DIR') ?: $baseDirSmarty . '/templates_c';
        $this->setCompileDir($compileDir);
        // Non riutilizzare compilazioni create da una diversa versione della libreria.
        $this->compile_id = 'smarty_' . self::SMARTY_VERSION;

        // Directory utilizzata per la cache
        $this->setCacheDir(getenv('LUMEA_SMARTY_CACHE_DIR') ?: $baseDirSmarty . '/cache');

        // Directory per eventuali file di configurazione Smarty
        $this->setConfigDir($baseDirSmarty . '/configs');
    }

    /** Cambia l'URL della risorsa quando cambia il contenuto, evitando CSS o JS obsoleti. */
    protected function versioneRisorsa(string $percorso): string
    {
        return substr(hash_file('sha256', __DIR__ . '/../' . $percorso), 0, 16);
    }

    /** La View emette la risposta di navigazione decisa dal Control. */
    public static function redirect(string $destinazione): void
    {
        $base = self::baseApplicazione();
        $percorso = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        // Su URL nuove annidate, un Location relativo sarebbe risolto nella directory sbagliata.
        // Le destinazioni e i codici delle richieste storiche rimangono esattamente invariati.
        if (is_string($percorso)
            && strpos($percorso, $base) === 0
            && $percorso !== $base
            && $percorso !== $base . 'index.php'
            && preg_match('/^index\.php(?:\?|$)/D', $destinazione) === 1) {
            $destinazione = $base . $destinazione;
        }
        header('Location: ' . $destinazione);
        exit;
    }

    /** Prefisso relativo all'origine, indipendente da hostname e cartella di installazione. */
    private static function baseApplicazione(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        return rtrim(str_replace('\\', '/', dirname($script)), '/.') . '/';
    }

    public function mostraErroreRichiesta(string $messaggio): void
    {
        $this->assign('messaggio', $messaggio);
        $this->setTemplate('richiesta_non_valida.tpl');
    }

    protected function setTemplate($template)
    {
        // Il token è un dato della View; i template lo includono solo nei POST.
        $this->assign('csrfToken', FSessione::tokenCsrf());
        $base = self::baseApplicazione();
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? $base) ?: [];
        $percorso = $uri['path'] ?? $base;
        // Le ancore restano sulla nostra origine anche quando il server riceve una URI assoluta.
        if (strpos($percorso, $base) !== 0
            || strpos($percorso, '//') === 0
            || strpos($percorso, '\\') !== false) {
            $percorso = $base;
        }
        $this->assign('baseApplicazione', $base);
        $this->assign('urlCorrente', $percorso . (isset($uri['query']) ? '?' . $uri['query'] : ''));
        header('Cache-Control: no-store');
        $this->display($template);
    }

    protected function setDataIntoTemplate($reference, $data)
    {
        $this->assign($reference, $data);
    }
}

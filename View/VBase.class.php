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

    public function mostraErroreRichiesta(string $messaggio): void
    {
        $this->assign('messaggio', $messaggio);
        $this->setTemplate('richiesta_non_valida.tpl');
    }

    protected function setTemplate($template)
    {
        // Il token è un dato della View; i template lo includono solo nei POST.
        $this->assign('csrfToken', FSessione::tokenCsrf());
        header('Cache-Control: no-store');
        $this->display($template);
    }

    protected function setDataIntoTemplate($reference, $data)
    {
        $this->assign($reference, $data);
    }
}

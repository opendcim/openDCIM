<?php
declare(strict_types=1);

namespace {
    use Slim\App;

    /** @var App $app */
    $app = null;

    /** @var People $person */
    $person = null;
}

/* ============================================================
 * PSR-7 / Slim
 * ============================================================ */
namespace Psr\Http\Message {
    interface ResponseInterface {
        public function withJson(mixed $data, int $status = 200): self;
    }
}

/* ============================================================
 * SNMP
 * ============================================================ */
namespace OSS_SNMP {
    class SNMP {
        public function __construct(mixed ...$args) {}
        public function useSystem(): self { return $this; }
        public function useIface(): self { return $this; }
        public function get(mixed ...$args): mixed { return null; }
        public function realWalk(mixed ...$args): array { return []; }
    }
}

/* ============================================================
 * GLOBAL NAMESPACE
 * ============================================================ */
namespace {

    /* =========================
     * Globals runtime
     * ========================= */
    global $config, $person, $dbh, $app;

    /* =========================
     * Traduction
     * ========================= */
    function __(string $msg, mixed ...$args): string { return $msg; }

    /* =========================
     * Helpers génériques
     * ========================= */
    function locale_number(mixed ...$args): string { return (string) ($args[0] ?? ''); }
    function isValidURL(mixed ...$args): bool { return true; }
    function attribsql(mixed ...$args): string { return (string) ($args[0] ?? ''); }

    /* =========================
     * SQL / Sécurité
     * ========================= */
    function sanitize(mixed ...$args): mixed { return $args[0] ?? null; }
    function sql(mixed ...$args): string { return (string) ($args[0] ?? ''); }
    function extendsql(mixed ...$args): string { return ''; }
    function float_sqlsafe(mixed ...$args): float { return (float) ($args[0] ?? 0); }
    function transform(mixed ...$args): mixed { return $args[0] ?? null; }

    /* =========================
     * Helpers OpenDCIM
     * ========================= */
    function findit(mixed ...$args): mixed { return null; }
    function ticksToTime(mixed ...$args): string { return ''; }
    function redirect(mixed ...$args): never { exit; }

    /* =========================
     * Media / Électricité
     * ========================= */
    function getConnector(mixed ...$args): mixed { return null; }
    function getRate(mixed ...$args): mixed { return null; }
    function getProtocol(mixed ...$args): mixed { return null; }
    function getPhase(mixed ...$args): mixed { return null; }
    function getVoltage(mixed ...$args): mixed { return null; }

    /* =========================
     * UI
     * ========================= */
    function updateNavTreeHTML(mixed ...$args): void {}
    function html2rgb(mixed ...$args): array { return [0, 0, 0]; }

    /* =========================
     * Constantes runtime
     * ========================= */
    const AUTHENTICATION = 1;

    /* =========================
     * Config OpenDCIM
     * ========================= */
    class Config {
        public array $ParameterArray = [];
    }

    /* =========================
     * SwiftMailer
     * ========================= */
    class Swift_SmtpTransport {
        public static function newInstance(mixed ...$args): self { return new self(); }
    }
    class Swift_Mailer {
        public static function newInstance(mixed ...$args): self { return new self(); }
    }
    class Swift_Message {
        public static function newInstance(mixed ...$args): self { return new self(); }
    }
    class Swift_Image {
        public static function fromPath(mixed ...$args): self { return new self(); }
    }
    class Swift_RfcComplianceException extends \Exception {}
    class Swift_TransportException extends \Exception {}

    /* =========================
     * Templates
     * ========================= */
    class TemplatePort {
        public int $TemplateID;
        public int $PortNumber;

        public function getPort(): mixed { return null; }
    }

    /* =========================
     * Dynamic properties
     * ========================= */
    class Device {
        public mixed $OMessage;
        public mixed $Reservation;
        public mixed $FailSafe;
    }

    class Cabinet {
        public mixed $Rights;
    }
}

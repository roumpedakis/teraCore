<?php
// scripts/install-git-hooks.php
$hookSource = __DIR__ . '/git-hooks/pre-commit';
$hookDestDir = getcwd() . '/.git/hooks';
if (!is_file($hookSource)) {
    fwrite(STDERR, "Hook source not found: $hookSource\n");
    exit(1);
}
if (!is_dir($hookDestDir)) {
    fwrite(STDERR, ".git/hooks directory not found. Are you running this from the repository root?\n");
    exit(2);
}
$hookDest = $hookDestDir . '/pre-commit';
if (!@copy($hookSource, $hookDest)) {
    fwrite(STDERR, "Failed to copy hook to $hookDest\n");
    exit(3);
}
@chmod($hookDest, 0755);
echo "Installed pre-commit hook to .git/hooks/pre-commit\n";
exit(0);

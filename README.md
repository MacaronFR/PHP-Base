# PHP-Base

# Syntaxe routeur

> []

ce qui est entre les crochets est optionnel

> {}

ce qui est entre accollade est un placeholder pour un paramètre dynamique (le mot entre les accolades sera la clé dans le tableau uri_args passé au controleur)

> \*

placeholder sans nom

> _

n'importe quel caratère en fin de ligne

example de route

> /fichier/{nom}\[/{extension}_\]

pourrait correspondre à

> /fichier/bonjour
> /fichier/bonjour/png
> /fichier/bonjour/png/aujourdhui
> /fichier/bonjour/pngdemain/aujourdhui/hier

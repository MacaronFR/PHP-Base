# PHP-Base

## Syntaxe routeur

### Métacaractère 

* []
* {}
* *
* _

#### Crochet
Tout caractère ou méta caractère placé entre sera optionnel dans le pattern.
Le pattern sera positif avec ou sans la présence du contenu
Les crochets ne sont pas compris dans le pattern en question

**Exemple**
```
pattern :
/route/vers/un/endroit[/optionnel]

URI :
/route/vers/un/endroit => match
/route/vers/un/endroit/optionnel => match
/route/vers/un/endroit/opt => pas de match
```

#### Accollades
Les accollades prennent la place d'un paramètre dynamique et peut donc être remplcacé par n'importe qu'elle suite de caractère hormis les `/`
Les caractères compris entre les accolades seront, lors du passage au controlleur, la clé des valeurs correspondantes.

**Exemples**
```
Route : 
/route/avec/un/{parametre1}/deux/{parametre2}

URI :
/route/avec/un/bonjour/deux/salut :
tableau de parametre passé au controlleur
[
    parametre1 => "bonjour",
    parametre2 => "salut"
]
```

#### Étoile
Au même titre que les accolades, tient lieu et place d'un nombre quelconque de tout caractère hormis le `/`
La clé das le tableau final sera sa position dans la liste de parametre (en partant de 0)

**Exemples**

```
Route :
/route/avec/un/{param}/et/sans/nom/*

URI :
/route/avec/un/bonjour/et/sans/nom/aurevoir
tableau de parametre passé au controlleur
[
    param => "bonjour",
    1 => "aurevoir"
]
```

**UnderScore (*Tiret du 8* ou *Tiret du Bas*)**
Remplace n'importe quel caractère (`/` compris)
Dans le tableau de parametre, il sera représenté par un nombre au même titre que l'étoile, selon sa position dans la liste des paramètre

```
Route :
/route/avec/un/{param}/et/sans/nom/*/et/_

URI :
/route/avec/un/bonjour/et/sans/nom/aurevoir/et/plein/de/chose/aléatoire

Tableau de parametre passé au controlleur
[
    param => "bonjour",
    1 => "aurevoir",
    2 => "plein/de/chose/aléatoire"
]
```

###Exemple utilisant toute les propriété

```
Route :
/fichier/{nom}\[/{extension}_\]

URI:
/fichier/bonjour => match  
/fichier/bonjour/png => match
/fichier/bonjour/png/aujourdhui => match  
/fichier/bonjour/pngdemain/aujourdhui/hier => match
```

##Utilisation des méthode du routeur

###get(string \$pattern, string \$controller, \$param)
Lorsque la méthode est en GET et que le `$pattern` match avec l'URI, instancie un nouveau `$controller` et lui ajoute les parametre additionnel `$param`. Ensuite la méthode get() du controlleur est exécuté.

###post(string \$pattern, string \$controller, \$param)
Lorsque la méthode est en POST et que le `$pattern` match avec l'URI, instancie un nouveau `$controller` et lui ajoute les parametre additionnel `$param`. Ensuite la méthode post() du controlleur est exécuté. Un tableau contenant le corps de la requete POST est instancié

###put(string \$pattern, string \$controller, \$param)
Lorsque la méthode est en PUT et que le `$pattern` match avec l'URI, instancie un nouveau `$controller` et lui ajoute les parametre additionnel `$param`. Ensuite la méthode put() du controlleur est exécuté. Un tableau contenant le corps de la requete PUT est instancié

###delete(string \$pattern, string \$controller, \$param)
Lorsque la méthode est en GET et que le `$pattern` match avec l'URI, instancie un nouveau `$controller` et lui ajoute les parametre additionnel `$param`. Ensuite la méthode delete() du controlleur est exécuté.

###route(string \$pattern, callable \$callback)
Lorsque l'URI match avec le pattern execute la fonction \$callback avec le prototype, `function callback(Routeur $routeur)`
Permet de subdiviser les routes en plusieurs sous groupes accélérant la vitesse d'éxécution d'une route

**Exemple**
```
$rt->route("/bonjour/_", function($rt){
    $rt->get("/bonjour/aurevoir", Controller1);
    $rt->post("/bonjour/{param}", Controller1);
    $rt->get("/bonjour/salut[/{test}], Controller2);
});
```

## Modèle

###Classe enfant
Les classes enfants de Model doivent instancier les paramètre suivant.
* $table_name
* $id_name
* $column

> **$table_name**

Nom de la table en Base sur laquelle ce Model s'applique

> **$id_name**
 
Nom de la colonne servant de clé primaire à la table sur laquelle le Model s'applique

> **$column**

Un tableau de la forme
```
[
    "nom_param1" => "nom_colonne1",
    "nom_param2" => "nom_colonne2",
    …
]
```
Où la clé est le nom donné à la colonne du coté php, et la valeur son nom en base (ceci permet de donné des alias plus pratique à écrire à des colonnes par exemple)

> **$max_row**

Peut être redéfini pour changer le nombre max de ligne récupéré en une seule requête (défaut 500).

###Fonction

Il existe plusieurs fonction prédéfinies permettant un grand nombre d'opérations

> **selectAll(int \$iteration = 0, bool \$limit = true): array;**

Permet de récupéré toute les lignes d'une table. Si `$limit` est à `true` la fonction ne recupèrera que `$max_row` ligne soit 500 lignes par défaut (comportement par défaut), `$iteration` sert à définir le début de la récupération (pagination).
Si `$limit` est a `false` alors toutes les lignes de la table seront récupérer en une seule fois.

> **selectTotal(): int;**

Permet de récupérer le nombre de ligne total de la table

> **select(array \$value, string \$where, string \$group = "", string \$order = "", int \$start = null, int \$limit = null, bool \$unique = true): array**

Permet de selectionner une ligne en fonction d'une condition saisie dans le `$where` sous forme de paramètre SQL (via la syntaxe :nom_du_param) dont la valeur sera contenu dans le tableau `$value` avec comme clé le `nom_du_param`

**Exemple**
```php
select(['id' => 3], "id_table=:id");
```
les autres paramètre permettent de personnnaliser quelque peu la requête, `$groups` permet d'ajouter une clause `GROUPS` à la requête. De même pour `$order`. `$start` et `$limit` permettent de définir le début et le nombre de ligne dans la clause `LIMIT`. Le paramètre `$unique` permet de spécifier si une seule ou plusieurs lignes seront retourné par la fonction.

//TODO

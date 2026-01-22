# Pour développer une fonctionnalité,

## Comment procéder (workflow)

1. Créer une nouvelle branche (brouillon)
    - ```git branch nom-de-la-branche```

2. Se positionner sur la branche
    - ```git switch nom-de-la-branche```

3. Développer la fonctionnalité

## Comment livrer notre code après avoir développé une fonctionnalité?

1. Vérifier la syntaxe du code
    - ```php vendor/bin/php-cs-fixer fix src```

2. Vérifier la logique
    - ```php vendor/bin/phpstan analyse```
    - Ignorer les erreurs qui en réalité ne le sont pas, dans le fichier de configuration de php stan
        - ```
            parameters:
                level: 6
                paths:
                    - src/
                    - tests/
                ignoreErrors:
                    - '#Call to function method_exists\(\) with .*Symfony\\\\Component\\\\Dotenv\\\\Dotenv.*bootEnv.*will always evaluate to true#'
                    - '#Property App\\Entity\\User::\$id \(int\|null\) is never assigned int so it can be removed from the property type#'```

3. Vérifier les linters
    - ```symfony console lint:twig```
    - ```symfony console lint:container```
    - ```symfony console lint:yaml config```

4. Sauvegarder et envoyer le code sur GitHub
    - Ajouter le code dans la zone de transit
        - ```git add .```
    - Sauvegarder l'application
        - ```git commit -m "Message du commit"```
    - Envoyer le code sur la branche créée
        - ```git push --set-upstream origin nom-de-la-branche```
    - Proposer ce code pour la fusion 
        - (Pull Request)
    - Après la revue de code, fusionner le brouillon (branche) au main 
        - (Merge Request) 

    - En local,
        - Switcher sur la branche `main`
        - Mettre à jour cette branche `git pull origin main`

# Internationalization (i18n) Documentation

This document explains the translation system for the CMS backoffice application.

## Overview

The application supports **French** (default) and **English** with a complete translation system covering:
- All user interface text
- Form labels and validation messages
- Flash messages and API responses
- Navigation and buttons

## Translation File Structure

```
translations/
├── messages.fr.yaml       # General UI text (French)
├── messages.en.yaml       # General UI text (English)
├── forms.fr.yaml          # Form labels & placeholders (French)
├── forms.en.yaml          # Form labels & placeholders (English)
├── validators.fr.yaml     # Validation messages (French)
├── validators.en.yaml     # Validation messages (English)
├── security.fr.yaml       # Authentication messages (French)
├── security.en.yaml       # Authentication messages (English)
└── README.md              # This file
```

## Translation Key Naming Convention

### Principle

Keys describe **purpose**, not **location**. This makes them easier to find and maintain.

```yaml
# ✅ GOOD - Describes purpose
nav:
  dashboard: Tableau de bord
article:
  flash:
    created: Article créé avec succès
button:
  save: Enregistrer

# ❌ BAD - Describes location
article_controller:
  flash:
    line_45: Article créé avec succès
```

### Key Organization Pattern

```yaml
{feature}:
  {type}:
    {field}: Translation value
```

**Example:**
```yaml
user:
  label:
    firstname: Prénom
    email: Email
  flash:
    created: Utilisateur créé avec succès
  error:
    not_found: Utilisateur introuvable
```

### Common Prefixes

- `label.*` - Form labels, field names
- `button.*` - Button text
- `flash.*` - Flash messages (success/error)
- `error.*` - Error messages
- `help.*` - Help text, tooltips
- `placeholder.*` - Input placeholders
- `table.*` - Table headers
- `modal.*` - Modal dialog text

## How to Use Translations

### In Controllers

```php
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {}

    public function create(): Response
    {
        // Simple translation
        $this->addFlash('success',
            $this->translator->trans('article.flash.created')
        );

        // Translation with parameters
        $this->addFlash('success',
            $this->translator->trans('user.flash.roles_updated', [
                '%name%' => $user->getName()
            ])
        );

        return $this->redirectToRoute('app_article_index');
    }
}
```

### In Templates (Twig)

```twig
{# Simple translation #}
<h1>{{ 'article.title.list'|trans }}</h1>

{# Translation with parameters #}
<p>{{ 'article.by_author'|trans({'%author%': article.author.name}) }}</p>

{# Translation with specific domain #}
{{ 'user.label.email'|trans({}, 'forms') }}

{# Trans block for longer text #}
{% trans with {'%name%': user.name} from 'messages' %}
    Welcome back, %name%!
{% endtrans %}
```

### In Forms

```php
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'article.label.title',
                'attr' => [
                    'placeholder' => 'article.placeholder.title'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'translation_domain' => 'forms', // Apply to all fields
        ]);
    }
}
```

## URL Structure

All routes include the locale for SEO:

```
/fr/dashboard    # French dashboard
/en/dashboard    # English dashboard
/fr/articles     # French articles page
/en/articles     # English articles page
```

## Language Switcher

The language switcher is located in the sidebar footer. It:
- Displays FR | EN buttons
- Highlights the active language
- Preserves the current page when switching
- Stores language preference in session

## Adding New Translations

### Step 1: Add to YAML Files

Add your translation keys to both language files:

```yaml
# translations/messages.fr.yaml
my_feature:
  title: Mon titre
  description: Ma description

# translations/messages.en.yaml
my_feature:
  title: My title
  description: My description
```

### Step 2: Use in Code

```twig
<h1>{{ 'my_feature.title'|trans }}</h1>
```

### Step 3: Clear Cache

```bash
php bin/console cache:clear
```

## Important Notes

### YAML Syntax Rules

1. **Quote values with colons:**
```yaml
# ✅ GOOD
format_hint: "Accepted format: JPEG, PNG"

# ❌ BAD - Will cause YAML parsing error
format_hint: Accepted format: JPEG, PNG
```

2. **Use consistent indentation (2 spaces)**

3. **Quote values with special characters:**
```yaml
message: "Don't forget!"  # Quote because of apostrophe
```

### Translation Parameters

Use `%parameter%` format for dynamic values:

```yaml
# Translation file
user:
  greeting: "Welcome, %name%!"

# In code
$translator->trans('user.greeting', ['%name%' => $user->getName()])
```

### Form Validation

Validation messages automatically use the `validators` translation domain:

```yaml
# translations/validators.fr.yaml
registration:
  password:
    too_short: Votre mot de passe doit contenir au moins {{ limit }} caractères
```

## Troubleshooting

### Translations not showing

1. Clear the cache: `php bin/console cache:clear`
2. Check the translation key exists in the YAML file
3. Verify the translation domain is correct
4. Check for YAML syntax errors

### YAML parsing errors

- Quote values containing colons (`:`)
- Check indentation (use 2 spaces, not tabs)
- Validate YAML syntax online if needed

### Wrong language displaying

1. Check the URL includes the correct locale (`/fr/` or `/en/`)
2. Clear browser cache and session
3. Verify `app.default_locale` in `config/services.yaml`

## Configuration Files

- `config/packages/translation.yaml` - Translation system configuration
- `config/services.yaml` - Supported locales parameter
- `config/routes.yaml` - Locale routing prefix
- `src/EventSubscriber/LocaleSubscriber.php` - Locale persistence

## Best Practices

1. **Always use translation keys** - Never hardcode text
2. **Be descriptive** - Use clear, meaningful key names
3. **Group logically** - Organize keys by feature
4. **Use parameters** - For dynamic content like names, dates
5. **Keep it DRY** - Reuse common translations (buttons, actions)
6. **Document complex keys** - Add YAML comments for context
7. **Test both languages** - Always verify translations work in FR and EN

## Need Help?

If you need to add a translation or modify existing text:

1. Find the appropriate YAML file (`messages`, `forms`, `validators`, or `security`)
2. Locate or create the translation key following the naming convention
3. Add the text in both French and English files
4. Clear the cache
5. Test in both languages using the language switcher

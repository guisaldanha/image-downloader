# Image Downloader

Uma biblioteca PHP capaz de fazer o download de imagens de sites e blogs sob uma licença MIT. Informe a URL e o sistema fará o download de todas as imagens dessa página, com opção de seguir os links do URL inicial e fazer o download dessas páginas secundárias, proporcionando um download de praticamente todas as imagens do site.

## Recursos

- Simples de usar, basta criar um objeto passando a URL alvo, a pasta onde as imagens serão salvas e informar se quer ou não que o sistema navegue pelos links do URL passado para fazer o download também dessas páginas secundárias.
- Todo o processo é mapeado através de arquivos logs

## Instalação

Para usar o sistema use composer:

```shell
composer require guisaldanha/image-downloader
```

## Exemplos de uso

```php
<?php

require __DIR__.'/vendor/autoload.php';

use GuiSaldanha\ImageDownloader\ImageDownloader;

set_time_limit(0);

$alvo = 'https://blog-de-exemplo.com/imagens-da-categoria.html';
$downloader = new  ImageDownloader($alvo, __DIR__.'/imagens-baixadas',true);
$imagens = $downloader->download();

foreach($imagens  as  $imagem){
$imagem = str_replace(__DIR__.'/', '', $imagem);
    echo  '<img src="'.$imagem.'" style="width:100px; height:100px; margin:10px;">';
}
```

## Limitações

- O sistema não interpreta Javascript. Caso os links sejam acionados pelo evento onClick ou outro método que não use o atributo `href` do elemento `a`
- O sistema não faz o download de imagens de backgrounds nem imagens que são adicionadas ao dom depois do carregamento da página, por javascript

<?php

namespace GuiSaldanha\ImageDownloader;

class ImageDownloader
{
	/**
	 * Url base para download das imagens
	 * 
	 * @var string
	 */
	private string $url;

	/**
	 * Lista de URLS onde serão buscadas as imagens a serem baixadas
	 * 
	 * @var array
	 */
	private array $urls;

	/**
	 * Caminho onde as imagens serão salvas
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Lista das imagens a serem baixadas
	 * 
	 * @var array
	 */
	private array $files;

	/**
	 * Conteúdo da imagem atual que está sendo baixada
	 * 
	 * @var string
	 */
	private string $image;

	/**
	 * Arquivo de log onde serão listadas as URLs acessadas
	 *
	 * @var string
	 */
	private string $logUrls = 'urls-acessadas.log';

	/**
	 * Arquivo de log onde serão listadas as imagens baixadas
	 *
	 * @var string
	 */
	private string $logImages = 'imagens-baixadas.log';

	/**
	 * Construtor da classe ImageDownloader que recebe a url onde estão as imagens e o caminho onde serão salvas.
	 *
	 * @param string $url				Url onde estão as imagens
	 * @param string $path				Caminho onde as imagens serão salvas
	 * @param boolean $sublinks			Informa se deve-se buscar imagens de links internos
	 */
	public function __construct($url, $path, $sublinks = false)
	{
		$this->url  = $url;
		$this->urls = $this->getUrls($url, $sublinks);
		$this->path = trim($path, '/') . '/';
	}

	/**
	 * Monta um array com as URLs onde estão as imagens
	 *
	 * @param string $url
	 * @param boolean $sublinks
	 * @return void
	 */
	public function getUrls($url, $sublinks)
	{
		$urls = [];
		$urls[] = $url;
		if ($sublinks) {
			$urls = array_merge($urls, $this->getSubUrls($url));
		}
		return $urls;
	}

	/**
	 * Busca as URLs de links internos da página
	 *
	 * @param string $url
	 * @return array
	 */
	public function getSubUrls($url)
	{
		$subUrls = [];
		$html = $this->openURL($url);
		$dom = new \DOMDocument();
		@$dom->loadHTML($html);
		$xpath = new \DOMXPath($dom);
		$hrefs = $xpath->evaluate("/html/body//a");
		foreach ($hrefs as $href) {
			$subUrls[] = $this->resolveLink($href->getAttribute('href'));
		}
		$subUrls = array_unique(array_filter($subUrls));
		return $subUrls;
	}

	/**
	 * Gera array com todas o URL de todas as imagens da página e coloca na propriedade $files
	 *
	 * @return array
	 */
	public function getImagesFromURLs()
	{
		$this->files = [];
		foreach ($this->urls as $url) {
			if (!empty($url) and !$this->inLog($this->logUrls, $url)) {
				$this->registraLog($this->logUrls, $url);
				$html = $this->openURL($url);
				$dom = new \DOMDocument();
				@$dom->loadHTML($html);
				$xpath = new \DOMXPath($dom);
				$images = $xpath->evaluate("/html/body//img");
				foreach ($images as $image) {
					$this->files[] = $this->resolveLink($image->getAttribute('src'));
				}
			}
		}
		$this->files = array_unique(array_filter($this->files));
	}

	/**
	 * Retorna array com todas as imagens que serão baixadas
	 *
	 * @return array
	 */
	public function getFiles()
	{
		if (empty($this->files)) {
			$this->getImagesFromURLs();
		}
		return $this->files;
	}

	/**
	 * Baixa as imagens e retorna um array com os nome dos arquivos baixados
	 *
	 * @return array
	 */
	public function download()
	{
		$files = $this->getFiles();
		$retorno = [];
		foreach ($files as $file) {
			if (!empty($file) and !$this->inLog($this->logImages, $file)) {
				$this->registraLog($this->logImages, $file);
				$this->image = $this->openURL($file);
				$retorno[] = $this->saveImage($file);
			}
		}
		return $retorno;
	}

	/**
	 * Salva a imagem no caminho informado e retorna o nome do arquivo
	 *
	 * @return string
	 */
	public function saveImage($file)
	{
		if (!is_dir($this->path)) {
			mkdir($this->path, 0777, true);
		}
		$file = $this->path . basename($file);
		file_put_contents($file, $this->image);
		return $file;
	}

	/**
	 * Retorna o conteúdo da URL informada
	 *
	 * @param string $url
	 * @return string
	 */
	public function openURL($url)
	{
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Pragma: ";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		// curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		// curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		$content = curl_exec($curl);
		curl_close($curl);
		return $content;
	}

	/**
	 * Retrona o link resolvido com a URL completa
	 *
	 * @param string $file
	 * @return string
	 */
	public function resolveLink($file)
	{
		$urlBase = parse_url($this->url, PHP_URL_SCHEME) . '://' . parse_url($this->url, PHP_URL_HOST);
		$file = str_replace($urlBase, '', $file);
		$initialFolder = parse_url($this->url, PHP_URL_PATH);
		$initialFolder =  trim(pathinfo($initialFolder, PATHINFO_DIRNAME), '/');
		$folders = explode('/', $initialFolder);
		$previusFolders = substr_count($file, '../');
		while ($previusFolders > 0) {
			array_pop($folders);
			$previusFolders--;
		}
		$file = str_replace('../', '', $file);
		$folders = implode('/', $folders);
		$file = $folders . '/' . $file;
		$file = $urlBase . '/' . trim($file, '/');
		// FAZ SUBSTITUIÇÕES PARA RESOLVER LINKS
		$file = str_replace([' ', '/\//', '/\/', '/\/', '/#', '/\\'], ['%20', '/', '/', '/', '/', '/'], $file);
		// RETORNA O LINK OU VAZIO SE FOR IMAGEM BASE64
		$file = strpos($file, ';base64,') === false ? $file : '';
		// RETORNA O LINK OU VAZIO SE FOR UM LINK PARA OUTRO SITE
		$file = strpos($file, '/https://') === false ? $file : '';
		$file = strpos($file, '/http://') === false ? $file : '';
		return $file;
	}

	/**
	 * Registra o log do evento
	 *
	 * @param string $log
	 * @param string $file
	 * @return void
	 */
	public function registraLog($file, $txt)
	{
		$log = fopen($file, 'a');
		fwrite($log, $txt . "\n");
		fclose($log);
	}

	/**
	 * Verifica se o texto está presente no arquivo, ou seja, se a URL já foi acessada
	 *
	 * @param string $file		Arquivo de log
	 * @param string $txt		Texto a ser verificado
	 * @return boolean
	 */
	public function inLog($file, $txt)
	{
		$log = fopen($file, 'r');
		$retorno = false;
		while (!feof($log)) {
			$line = fgets($log);
			if (strpos($line, $txt) !== false) {
				$retorno = true;
				break;
			}
		}
		fclose($log);
		return $retorno;
	}
}

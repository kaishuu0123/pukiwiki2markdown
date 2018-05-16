<?php
// PukiWiki Plus! - Yet another WikiWikiWeb clone.
// $Id: html.php,v 1.65.30 2009/03/26 03:21:00 upk Exp $
// Copyright (C)
//   2005-2009 PukiWiki Plus! Team
//   2002-2007 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// HTML-publishing related functions
// Plus!NOTE:(policy)not merge official cvs(1.49->1.54)
// Plus!NOTE:(policy)not merge official cvs(1.58->1.59) See Question/181

// Show page-content
function catbody($title, $page, $body)
{
	global $script; // MUST BE SKIN.FILE. Do not delete line.
	global $vars, $arg, $defaultpage, $whatsnew, $help_page, $hr;
	global $attach_link, $related_link, $function_freeze;
	global $search_word_color, $foot_explain, $note_hr, $head_tags, $foot_tags;
	global $trackback, $referer, $javascript;
	global $newtitle, $newbase, $language, $use_local_time; // Plus! skin extension
	global $nofollow;
	global $_LANG, $_LINK, $_IMAGE;

	global $pkwk_dtd;     // XHTML 1.1, XHTML1.0, HTML 4.01 Transitional...
	global $page_title;   // Title of this site
	global $do_backup;    // Do backup or not
	global $modifier;     // Site administrator's  web page
	global $modifierlink; // Site administrator's name

	global $skin_file, $menubar, $sidebar;
	global $_string;

	if (! defined('SKIN_FILE') || ! file_exists(SKIN_FILE) || ! is_readable(SKIN_FILE)) {
		if (! file_exists($skin_file) || ! is_readable($skin_file)) {
			die_message(SKIN_FILE . '(skin file) is not found.');
		} else {
			define('SKIN_FILE', $skin_file);
		}
	}

	$_LINK = $_IMAGE = array();

	// Add JavaScript header when ...
	if (! PKWK_ALLOW_JAVASCRIPT) unset($javascript);

	$_page  = isset($vars['page']) ? $vars['page'] : '';
	$r_page = rawurlencode($_page);

	// Set $_LINK for skin
	$_LINK['add']        = get_cmd_uri('add',$_page);
	$_LINK['backup']     = get_cmd_uri('backup',$_page);
	$_LINK['brokenlink'] = get_cmd_uri('brokenlink',$_page);
	$_LINK['copy']       = get_cmd_uri('template','','', 'refer='.$r_page);
	$_LINK['diff']       = get_cmd_uri('diff',$_page);
	$_LINK['edit']       = get_cmd_uri('edit',$_page);
	$_LINK['guiedit']    = get_cmd_uri('guiedit',$_page);
	$_LINK['filelist']   = get_cmd_uri('filelist');
	$_LINK['freeze']     = get_cmd_uri('freeze',$_page);
	$_LINK['help']       = get_cmd_uri('help');
	$_LINK['linklist']   = get_cmd_uri('linklist',$_page);
	$_LINK['list']       = get_cmd_uri('list');
	$_LINK['log_login']  = get_cmd_uri('logview','','','kind=login');
	$_LINK['log_browse'] = get_cmd_uri('logview',$_page,'','kind=browse');
	$_LINK['log_update'] = get_cmd_uri('logview',$_page);
	$_LINK['log_down']   = get_cmd_uri('logview',$_page,'','kind=download');
	$_LINK['log_check']  = get_cmd_uri('logview',$_page,'','kind=check');
	$_LINK['menu']       = get_page_uri($menubar);
	$_LINK['new']        = get_cmd_uri('newpage','','','refer='.$r_page);
	$_LINK['newsub']     = get_cmd_uri('newpage_subdir','','','directory='.$r_page);
	$_LINK['print']      = get_cmd_uri('print',$_page);
	$_LINK['full']       = get_cmd_uri('print',$_page).'&amp;nohead&amp;nofoot';
	$_LINK['read']       = get_page_uri($_page);
	$_LINK['recent']     = get_page_uri($whatsnew);
	$_LINK['refer']      = get_cmd_uri('referer',$_page);
	$_LINK['reload']     = get_page_absuri($_page); // 本当は、get_script_uri でいいけど、絶対パスでないと、スキンに影響が出る
	$_LINK['reload_rel'] = get_page_uri($_page);
	$_LINK['rename']     = get_cmd_uri('rename','','','refer='.$r_page);
	$_LINK['skeylist']   = get_cmd_uri('skeylist',$_page);
	$_LINK['search']     = get_cmd_uri('search');
	$_LINK['side']       = get_page_uri($sidebar);
	$_LINK['source']     = get_cmd_uri('source',$_page);
	$_LINK['template']   = get_cmd_uri('template','','','refer='.$r_page);
	$_LINK['top']        = get_page_uri($defaultpage);
	if ($trackback) {
		$tb_id = tb_get_id($_page);
		$_LINK['trackback'] = get_cmd_uri('tb','','','__mode=view&tb_id='.$tb_id);
	}
	$_LINK['unfreeze']   = get_cmd_uri('unfreeze',$_page);
	$_LINK['upload']     = get_cmd_uri('attach',$_page,'','pcmd=upload');
	// link rel="alternate" にも利用するため absuri にしておく
	$_LINK['rdf']        = get_cmd_absuri('rss','','ver=1.0');
	$_LINK['rss']        = get_cmd_absuri('rss');
	$_LINK['rss10']      = get_cmd_absuri('rss','','ver=1.0'); // Same as 'rdf'
	$_LINK['rss20']      = get_cmd_absuri('rss','','ver=2.0');
	$_LINK['mixirss']    = get_cmd_absuri('mixirss');         // Same as 'rdf' for mixi

	// Compat: Skins for 1.4.4 and before
	$link_add        = & $_LINK['add'];
	$link_backup     = & $_LINK['backup'];
	$link_brokenlink = & $_LINK['brokenlink'];
	$link_template   = & $_LINK['copy'];
	$link_diff       = & $_LINK['diff'];
	$link_edit       = & $_LINK['edit'];
	$link_guiedit    = & $_LINK['guiedit'];
	$link_filelist   = & $_LINK['filelist'];
	$link_freeze     = & $_LINK['freeze'];
	$link_help       = & $_LINK['help'];
	$link_linklist   = & $_LINK['linklist'];
	$link_list       = & $_LINK['list'];
	$link_log_login  = & $_LINK['log_login'];
	$link_log_browse = & $_LINK['log_browse'];
	$link_log_update = & $_LINK['log_update'];
	$link_log_down   = & $_LINK['log_down'];
	$link_log_check  = & $_LINK['log_check'];
	$link_menu       = & $_LINK['menu'];
	$link_new        = & $_LINK['new'];
	$link_newsub     = & $_LINK['newsub'];
	$link_print      = & $_LINK['print'];
	$link_full       = & $_LINK['full'];
	$link_read       = & $_LINK['read'];
	$link_whatsnew   = & $_LINK['recent'];
	$link_refer      = & $_LINK['refer'];
	$link_reload     = & $_LINK['reload'];
	$link_reload_rel = & $_LINK['reload_rel'];
	$link_rename     = & $_LINK['rename'];
	$link_skeylist   = & $_LINK['skeylist'];
	$link_search     = & $_LINK['search'];
	$link_side       = & $_LINK['side'];
	$link_source     = & $_LINK['source'];
	$link_top        = & $_LINK['top'];
	if ($trackback) {
		$link_trackback  = & $_LINK['trackback'];
	}
	$link_unfreeze   = & $_LINK['unfreeze'];
	$link_upload     = & $_LINK['upload'];
	//
	$link_rdf        = & $_LINK['rdf'];
	$link_rss        = & $_LINK['rss'];
	$link_rss10      = & $_LINK['rss10'];
	$link_rss20      = & $_LINK['rss20'];
	$link_mixirss    = & $_LINK['mixirss'];

	// Init flags
	$is_page = (is_pagename($_page) && ! arg_check('backup') && ! is_cantedit($_page));
	$is_read = (arg_check('read') && is_page($_page));
	$is_freeze = is_freeze($_page);

	// Last modification date (string) of the page
	$lastmodified = $is_read ?  get_date('D, d M Y H:i:s T', get_filetime($_page)) .
		' ' . get_pg_passage($_page, FALSE) : '';

	// List of attached files to the page
	$attaches = '';
	if ($attach_link && $is_read && exist_plugin_action('attach')) {
		if (do_plugin_init('attach') !== FALSE) {
			$attaches = attach_filelist();
		}
	}

	// List of related pages
	$related  = ($related_link && $is_read) ? make_related($_page) : '';

	// List of footnotes
	ksort($foot_explain, SORT_NUMERIC);
	$notes = ! empty($foot_explain) ? $note_hr . join("\n", $foot_explain) : '';

	// Tags will be inserted into <head></head>
	$head_tag = ! empty($head_tags) ? join("\n", $head_tags) ."\n" : '';
	$foot_tag = ! empty($foot_tags) ? join("\n", $foot_tags) ."\n" : '';

	// 1.3.x compat
	// Last modification date (UNIX timestamp) of the page
	$fmt = $is_read ? get_filetime($_page) : 0;

	// Search words
	if ($search_word_color && isset($vars['word'])) {
		$body = '<div class="small">' . $_string['word'] . htmlspecialchars($vars['word']) .
			'</div>' . $hr . "\n" . $body;

		// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
		$words = preg_split('/\s+/', $vars['word'], -1, PREG_SPLIT_NO_EMPTY);
		$words = array_splice($words, 0, 10); // Max: 10 words
		$words = array_flip($words);

		$keys = array();
		foreach ($words as $word=>$id) $keys[$word] = strlen($word);
		arsort($keys, SORT_NUMERIC);
		$keys = get_search_words(array_keys($keys), TRUE);
		$id = 0;
		foreach ($keys as $key=>$pattern) {
			$s_key    = htmlspecialchars($key);
			$pattern  = '/' .
				'<textarea[^>]*>.*?<\/textarea>' .	// Ignore textareas
				'|' . '<[^>]*>' .			// Ignore tags
				'|' . '&[^;]+;' .			// Ignore entities
				'|' . '(' . $pattern . ')' .		// $matches[1]: Regex for a search word
				'/sS';
			$decorate_Nth_word = create_function(
				'$matches',
				'return (isset($matches[1])) ? ' .
					'\'<strong class="word' .
						$id .
					'">\' . $matches[1] . \'</strong>\' : ' .
					'$matches[0];'
			);
			$body  = preg_replace_callback($pattern, $decorate_Nth_word, $body);
			$notes = preg_replace_callback($pattern, $decorate_Nth_word, $notes);
			++$id;
		}
	}

	// Compat: 'HTML convert time' without time about MenuBar and skin
	$taketime = elapsedtime();

	require(SKIN_FILE);
}

// Related pages
function make_related($page, $tag = '')
{
	global $vars, $rule_related_str, $related_str;
	global $_ul_left_margin, $_ul_margin, $_list_pad_str;

	$links = links_get_related($page);

	if ($tag) {
		ksort($links, SORT_STRING);	// Page name, alphabetical order
	} else {
		arsort($links, SORT_NUMERIC);	// Last modified date, newer
	}

	$_links = array();
	foreach ($links as $page=>$lastmod) {
		if (check_non_list($page)) continue;

		$s_page   = htmlspecialchars($page);
		$passage  = get_passage($lastmod);
		$_links[] = $tag ?
			'<a href="' . get_page_uri($page) . '" title="' .
			$s_page . ' ' . $passage . '">' . $s_page . '</a>' :
			'<a href="' . get_page_uri($page) . '">' .
			$s_page . '</a>' . $passage;
	}
	if (empty($_links)) return ''; // Nothing

	if ($tag == 'p') { // From the line-head
		$margin = $_ul_left_margin + $_ul_margin;
		$style  = sprintf($_list_pad_str, 1, $margin, $margin);
		$retval =  "\n" . '<ul' . $style . '>' . "\n" .
			'<li>' . join($rule_related_str, $_links) . '</li>' . "\n" .
			'</ul>' . "\n";
	} else if ($tag) {
		$retval = join($rule_related_str, $_links);
	} else {
		$retval = join($related_str, $_links);
	}

	return $retval;
}

$line_rules = array(
	'COLOR\(([^\(\)]*)\){([^}]*)}'	=> '<span style="color:$1">$2</span>',
	'SIZE\(([^\(\)]*)\){([^}]*)}'	=> '<span style="font-size:$1px">$2</span>',
	'COLOR\(([^\(\)]*)\):((?:(?!COLOR\([^\)]+\)\:).)*)'	=> '<span style="color:$1">$2</span>',
	'SIZE\(([^\(\)]*)\):((?:(?!SIZE\([^\)]+\)\:).)*)'	=> '<span class="size$1">$2</span>',
	'%%%(?!%)((?:(?!%%%).)*)%%%'	=> '<ins>$1</ins>',
	'%%(?!%)((?:(?!%%).)*)%%'	=> '<del>$1</del>',
	"'''(?!')((?:(?!''').)*)'''"	=> '<em>$1</em>',
	"''(?!')((?:(?!'').)*)''"	=> '<strong>$1</strong>',
);

// User-defined rules (convert without replacing source)
function make_line_rules($str)
{
	global $line_rules;
	static $pattern, $replace;

	if (! isset($pattern)) {
        $pattern = array_map(create_function('$a',
			'return \'/\' . $a . \'/\';'), array_keys($line_rules));
		$replace = array_values($line_rules);
		unset($line_rules);
	}

	return preg_replace($pattern, $replace, $str);
}

// Remove all HTML tags(or just anchor tags), and WikiName-speific decorations
function strip_htmltag($str, $all = TRUE)
{
	global $_symbol_noexists;
	static $noexists_pattern;

	if (! isset($noexists_pattern))
		$noexists_pattern = '#<span class="noexists">([^<]*)<a[^>]+>' .
			preg_quote($_symbol_noexists, '#') . '</a></span>#';

	// Strip Dagnling-Link decoration (Tags and "$_symbol_noexists")
	$str = preg_replace($noexists_pattern, '$1', $str);

	if ($all) {
		// All other HTML tags
		return preg_replace('#<[^>]+>#', '', $str);
	} else {
		// All other anchor-tags only
		return preg_replace('#<a[^>]+>|</a>#i', '', $str);
	}
}

// Remove AutoLink marker with AutoLink itself
function strip_autolink($str)
{
	return preg_replace('#<!--autolink--><a [^>]+>|</a><!--/autolink-->#', '', $str);
}

// Make a backlink. searching-link of the page name, by the page name, for the page name
function make_search($page)
{
	return '<a href="' . get_cmd_uri('related',$page) . '">' . htmlspecialchars($page) . '</a> ';
}
?>
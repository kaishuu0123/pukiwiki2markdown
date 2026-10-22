<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: convert_html.php,v 1.21 2011/01/25 15:01:01 henoheno Exp $
// Copyright (C)
//   2002-2005 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// function 'convert_html()', wiki text parser
// and related classes-and-functions

require_once 'make_link.php';

function convert_html($lines)
{
	global $vars, $digest;
	static $contents_id = 0;

	// Set digest
	// $digest = md5(join('', get_source($vars['page'])));

	if (! is_array($lines)) $lines = explode("\n", $lines);

	$body = new Body(++$contents_id);
	$body->parse($lines);

	return $body->toString();
}

// Make heading string (remove heading-related decorations from Wiki text)
function make_heading(& $str, $strip = TRUE)
{
	global $NotePattern;
	// Cut fixed-heading anchors
	$id = '';
	$matches = array();
	if (preg_match('/^(\*{0,3})(.*?)\[#([A-Za-z][\w-]+)\](.*?)$/m', $str, $matches)) {
		$str = $matches[2] . $matches[4];
		$id  = & $matches[3];
	} else {
		$str = preg_replace('/^\*{0,3}/', '', $str);
	}
	// Cut footnotes and tags
	if ($strip === TRUE) {
		if (empty($NotePattern)) {
			return $id;
		}
		$str = strip_htmltag(make_link(preg_replace($NotePattern, '', $str)));
	}
	return $id;
}

// Block elements
class Element
{
	public $parent;
	public $elements; // References of childs
	public $last;     // Insert new one at the back of the $last

	public function __construct()
	{
		$this->elements = array();
		$this->last     = & $this;
	}

	public function setParent($parent)
	{
		$this->parent = & $parent;
	}

	public function add($obj)
	{
		if ($this->canContain($obj)) {
			return $this->insert($obj);
		} else {
			return $this->parent->add($obj);
		}
	}

	public function insert($obj)
	{
		$obj->setParent($this);
		$this->elements[] = $obj;

		return $this->last = $obj->last;
	}

	public function canContain($obj)
	{
		return TRUE;
	}

	public function wrap($string, $tag, $param = '', $canomit = TRUE)
	{
		return ($canomit && $string == '') ? '' :
			'<' . $tag . $param . '>' . $string . '</' . $tag . '>';
	}

	public function toString()
	{
		$ret = array();
		foreach (array_keys($this->elements) as $key)
			$ret[] = $this->elements[$key]->toString();
		return join("\n", $ret);
	}

	public function dump($indent = 0)
	{
		$ret = str_repeat(' ', $indent) . get_class($this) . "\n";
		$indent += 2;
		foreach (array_keys($this->elements) as $key) {
			$ret .= is_object($this->elements[$key]) ?
				$this->elements[$key]->dump($indent) : '';
				//str_repeat(' ', $indent) . $this->elements[$key];
		}
		return $ret;
	}
}

// Returns inline-related object
function Factory_Inline($text)
{
	// Check the first letter of the line
	if (substr($text, 0, 1) == '~') {
		return new Paragraph(' ' . substr($text, 1));
	} else {
		return new Inline($text);
	}
}

function Factory_DList($root, $text)
{
	$out = explode('|', ltrim($text), 2);
	if (count($out) < 2) {
		return Factory_Inline($text);
	} else {
		return new DList($out);
	}
}

// '|'-separated table
function Factory_Table($root, $text)
{
	if (! preg_match('/^\|(.+)\|([hHfFcC]?)$/', $text, $out)) {
		return Factory_Inline($text);
	} else {
		return new Table($out);
	}
}

// Comma-separated table
function Factory_YTable($root, $text)
{
	if ($text == ',') {
		return Factory_Inline($text);
	} else {
		return new YTable(csv_explode(',', substr($text, 1)));
	}
}

function Factory_Div($root, $text)
{
	$matches = array();

	// Hack code
	if(preg_match('/^#([^\(\{]+)(?:\(([^\r]*)\))?(\{*)/', $text, $matches)) {
		$len  = strlen($matches[3]);
		$body = array();
		if ($len == 0) {
			return new Div($matches); // Seems legacy block plugin
		} else if (preg_match('/\{{' . $len . '}\s*\r(.*)\r\}{' . $len . '}/', $text, $body)) {
			$matches[2] .= "\r" . $body[1] . "\r";
			return new Div($matches); // Seems multiline-enabled block plugin
		}
	}

	return new Paragraph($text);
}

// Inline elements
class Inline extends Element
{
	public function __construct($text)
	{
		parent::__construct();
		$this->elements[] = trim((substr($text, 0, 1) == "\n") ?
			$text : make_link($text));
		// $this->elements[] = trim($text);
	}

	public function insert($obj)
	{
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	public function canContain($obj)
	{
		return is_a($obj, 'Inline');
	}

	public function toString()
	{
		global $line_break;
		return join(($line_break ? '<br />' . "\n" : "\n"), $this->elements);
	}

	public function toPara($class = '')
	{
		$obj = new Paragraph('', $class);
		$obj->insert($this);
		return $obj;
	}
}

// Paragraph: blank-line-separated sentences
class Paragraph extends Element
{
	public $param;

	public function __construct($text, $param = '')
	{
		parent::__construct();
		$this->param = $param;
		if ($text == '') return;

		if (substr($text, 0, 1) == '~')
			$text = ' ' . substr($text, 1);

		$this->insert(Factory_Inline($text));
	}

	public function canContain($obj)
	{
		return is_a($obj, 'Inline');
	}

	public function toString()
	{
		return parent::toString();
		return $this->wrap(parent::toString(), 'p', $this->param);
	}
}

// * Heading1
// ** Heading2
// *** Heading3
class Heading extends Element
{
	public $level;
	public $id;
	public $msg_top;

	public function __construct($root, $text)
	{
		parent::__construct();

		$this->level = min(3, strspn($text, '*'));
		list($text, $this->msg_top, $this->id) = $root->getAnchor($text, $this->level);
		$this->insert(Factory_Inline($text));
		$this->level++; // h2,h3,h4
	}

	public function insert($obj)
	{
		parent::insert($obj);
		return $this->last = & $this;
	}

	public function canContain($obj)
	{
		return FALSE;
	}

	public function toString()
	{
		// return $this->msg_top .  $this->wrap(parent::toString(),
		// 	'h' . $this->level, ' id="' . $this->id . '"');
		return str_repeat('#', $this->level - 1) . ' ' . parent::toString();
	}
}

// ----
// Horizontal Rule
class HRule extends Element
{
	public function __construct($root, $text)
	{
		parent::__construct();
	}

	public function canContain($obj)
	{
		return FALSE;
	}

	public function toString()
	{
		global $hr;
		return $hr;
	}
}

// Lists (UL, OL, DL)
class ListContainer extends Element
{
	public $tag;
	public $tag2;
	public $level;
	public $style;
	public $margin;
	public $left_margin;

	public function __construct($tag, $tag2, $head, $text)
	{
		parent::__construct();

		$var_margin      = '_' . $tag . '_margin';
		$var_left_margin = '_' . $tag . '_left_margin';
		global $$var_margin, $$var_left_margin;

		$this->margin      = $$var_margin;
		$this->left_margin = $$var_left_margin;

		$this->tag   = $tag;
		$this->tag2  = $tag2;
		$this->level = min(3, strspn($text, $head));
		$text = ltrim(substr($text, $this->level));

		parent::insert(new ListElement($this->level, $tag2));
		if ($text != '')
			$this->last = $this->last->insert(Factory_Inline($text));
	}

	public function canContain($obj)
	{
		return (! is_a($obj, 'ListContainer')
			|| ($this->tag == $obj->tag && $this->level == $obj->level));
	}

	public function setParent($parent)
	{
		global $_list_pad_str;

		parent::setParent($parent);

		$step = $this->level;
		if (isset($parent->parent) && is_a($parent->parent, 'ListContainer'))
			$step -= $parent->parent->level;

		$margin = $this->margin * $step;
		if ($step == $this->level)
			$margin += $this->left_margin;

		$this->style = sprintf($_list_pad_str, $this->level, $margin, $margin);
	}

	public function insert($obj)
	{
		if (! is_a($obj, get_class($this)))
			return $this->last = $this->last->insert($obj);

		// Break if no elements found (BugTrack/524)
		if (count($obj->elements) == 1 && empty($obj->elements[0]->elements))
			return $this->last->parent; // up to ListElement

		// Move elements
		foreach(array_keys($obj->elements) as $key)
			parent::insert($obj->elements[$key]);

		return $this->last;
	}

	public function toString()
	{
		return parent::toString();
		// return $this->wrap(parent::toString(), $this->tag, $this->style);
	}
}

class ListElement extends Element
{
	public $level;
	public $head;
	public function __construct($level, $head)
	{
		parent::__construct();
		$this->level = $level;
		$this->head  = $head;
	}

	public function canContain($obj)
	{
		return (! is_a($obj, 'ListContainer') || ($obj->level > $this->level));
	}

	public function toString()
	{
		return ($this->level == 1 ? '' : str_repeat(' ', (($this->level - 1) * 4)))
			. $this->head . ' ' . parent::toString();
		return $this->wrap(parent::toString(), $this->head);
	}
}

// - One
// - Two
// - Three
class UList extends ListContainer
{
	public function __construct($root, $text)
	{
		// parent::__construct('ul', 'li', '-', $text);
		parent::__construct('ul', '*', '-', $text);
	}
}

// + One
// + Two
// + Three
class OList extends ListContainer
{
	public function __construct($root, $text)
	{
		parent::__construct(null, '1.', '+', $text);
	}
}

// : definition1 | description1
// : definition2 | description2
// : definition3 | description3
class DList extends ListContainer
{
	public function __construct($out)
	{
		parent::__construct('dl', 'dt', ':', $out[0]);
		$this->last = Element::insert(new ListElement($this->level, 'dd'));
		if ($out[1] != '')
			$this->last = $this->last->insert(Factory_Inline($out[1]));
	}
}

// > Someting cited
// > like E-mail text
class BQuote extends Element
{
	public $level;

	public function __construct($root, $text)
	{
		parent::__construct();

		$head = substr($text, 0, 1);
		$this->level = min(3, strspn($text, $head));
		$text = ltrim(substr($text, $this->level));

		if ($head == '<') { // Blockquote close
			$level       = $this->level;
			$this->level = 0;
			$this->last  = $this->end($root, $level);
			if ($text != '')
				$this->last = $this->last->insert(Factory_Inline($text));
		} else {
			$this->insert(Factory_Inline($text));
		}
	}

	public function canContain($obj)
	{
		return (! is_a($obj, get_class($this)) || $obj->level >= $this->level);
	}

	public function insert($obj)
	{
		// BugTrack/521, BugTrack/545
		if (is_a($obj, 'inline'))
			return parent::insert($obj->toPara(' class="quotation"'));

		if (is_a($obj, 'BQuote') && $obj->level == $this->level && count($obj->elements)) {
			$obj = & $obj->elements[0];
			if (is_a($this->last, 'Paragraph') && count($obj->elements))
				$obj = & $obj->elements[0];
		}
		return parent::insert($obj);
	}

	public function toString()
	{
		return $this->wrap(parent::toString(), 'blockquote');
	}

	public function end($root, $level)
	{
		$parent = & $root->last;

		while (is_object($parent)) {
			if (is_a($parent, 'BQuote') && $parent->level == $level)
				return $parent->parent;
			$parent = & $parent->parent;
		}
		return $this;
	}
}

class TableCell extends Element
{
	public $tag = 'td'; // {td|th}
	public $colspan = 1;
	public $rowspan = 1;
	public $style; // is array('width'=>, 'align'=>...);
	public $part;

	public function __construct($text, $is_template = FALSE)
	{
		parent::__construct();
		$this->style = $matches = array();

		while (preg_match('/^(?:(LEFT|CENTER|RIGHT)|(BG)?COLOR\(([#\w]+)\)|SIZE\((\d+)\)):(.*)$/',
		    $text, $matches)) {
			if ($matches[1]) {
				$this->style['align'] = 'text-align:' . strtolower($matches[1]) . ';';
				$text = $matches[5];
			} else if ($matches[3]) {
				$name = $matches[2] ? 'background-color' : 'color';
				$this->style[$name] = $name . ':' . htmlsc($matches[3]) . ';';
				$text = $matches[5];
			} else if ($matches[4]) {
				$this->style['size'] = 'font-size:' . htmlsc($matches[4]) . 'px;';
				$text = $matches[5];
			}
		}
		if ($is_template && is_numeric($text))
			$this->style['width'] = 'width:' . $text . 'px;';

		if ($text == '>') {
			$this->colspan = 0;
		} else if ($text == '~') {
			$this->rowspan = 0;
		} else if (substr($text, 0, 1) == '~') {
			$this->tag = 'th';
			$text      = substr($text, 1);
		}

		if ($text != '' && $text[0] == '#') {
			// Try using Div class for this $text
			$obj = Factory_Div($this, $text);
			if (is_a($obj, 'Paragraph'))
				$obj = & $obj->elements[0];
		} else {
			$obj = Factory_Inline($text);
		}

		$this->insert($obj);
	}

	public function setStyle(& $style)
	{
		foreach ($style as $key=>$value)
			if (! isset($this->style[$key]))
				$this->style[$key] = $value;
	}

	public function toString()
	{
		if ($this->rowspan == 0 || $this->colspan == 0) return '';

		// $param = ' class="style_' . $this->tag . '"';
		$param = '';
		if ($this->rowspan > 1)
			$param .= ' rowspan="' . $this->rowspan . '"';
		if ($this->colspan > 1) {
			$param .= ' colspan="' . $this->colspan . '"';
			unset($this->style['width']);
		}
		if (! empty($this->style))
			$param .= ' style="' . join(' ', $this->style) . '"';

		if ($this->part == 'thead') {
			$this->tag = 'th';
		}

		return $this->wrap(parent::toString(), $this->tag, $param, FALSE);
	}
}

// | title1 | title2 | title3 |
// | cell1  | cell2  | cell3  |
// | cell4  | cell5  | cell6  |
class Table extends Element
{
	public $type;
	public $types;
	public $col; // number of column

	public function __construct($out)
	{
		parent::__construct();

		$cells       = explode('|', $out[1]);
		$this->col   = count($cells);
		$this->type  = strtolower($out[2]);
		$this->types = array($this->type);
		$is_template = ($this->type == 'c');
		$row = array();
		foreach ($cells as $cell)
			$row[] = new TableCell($cell, $is_template);
		$this->elements[] = $row;
	}

	public function canContain($obj)
	{
		return is_a($obj, 'Table') && ($obj->col == $this->col);
	}

	public function insert($obj)
	{
		$this->elements[] = $obj->elements[0];
		$this->types[]    = $obj->type;
		return $this;
	}

	public function toString()
	{
		static $parts = array('h'=>'thead', 'f'=>'tfoot', ''=>'tbody');

		// Set rowspan (from bottom, to top)
		for ($ncol = 0; $ncol < $this->col; $ncol++) {
			$rowspan = 1;
			foreach (array_reverse(array_keys($this->elements)) as $nrow) {
				$row = & $this->elements[$nrow];
				if ($row[$ncol]->rowspan == 0) {
					++$rowspan;
					continue;
				}
				$row[$ncol]->rowspan = $rowspan;
				// Inherits row type
				while (--$rowspan)
					$this->types[$nrow + $rowspan] = $this->types[$nrow];
				$rowspan = 1;
			}
		}

		// Set colspan and style
		$stylerow = NULL;
		foreach (array_keys($this->elements) as $nrow) {
			$row = & $this->elements[$nrow];
			if ($this->types[$nrow] == 'c')
				$stylerow = & $row;
			$colspan = 1;
			foreach (array_keys($row) as $ncol) {
				if ($row[$ncol]->colspan == 0) {
					++$colspan;
					continue;
				}
				$row[$ncol]->colspan = $colspan;
				if ($stylerow !== NULL) {
					$row[$ncol]->setStyle($stylerow[$ncol]->style);
					// Inherits column style
					while (--$colspan)
						$row[$ncol - $colspan]->setStyle($stylerow[$ncol]->style);
				}
				$colspan = 1;
			}
		}

		// toString
		$string = '';
		foreach ($parts as $type => $part)
		{
			$part_string = '';
			foreach (array_keys($this->elements) as $nrow) {
				if ($this->types[$nrow] != $type)
					continue;
				$row        = & $this->elements[$nrow];
				$row_string = '';
				foreach (array_keys($row) as $ncol) {
					$row[$ncol]->part = $part;
					$row_string .= $row[$ncol]->toString();
				}
				$part_string .= $this->wrap($row_string, 'tr');
			}
			$string .= $this->wrap($part_string, $part);
		}
		// $string = $this->wrap($string, 'table', ' class="style_table" cellspacing="1" border="0"');
		$string = $this->wrap($string, 'table', ' class="table table-bordered"');

		// return $this->wrap($string, 'div', ' class="ie5"');
		// return tidy_repair_string($string, ['indent' => true, 'show-body-only' => true], 'utf8') . "\n";
		$dom = new DOMDocument();

		$dom->preserveWhiteSpace = false;
		$string = mb_encode_numericentity($string, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
		$dom->loadHTML($string, LIBXML_HTML_NOIMPLIED);
		$dom->formatOutput = true;

		return $dom->saveXML($dom->documentElement);
	}
}

// , title1 , title2 , title3
// , cell1  , cell2  , cell3
// , cell4  , cell5  , cell6
class YTable extends Element
{
	public $col;

	public function __construct($_value)
	{
		parent::__construct();

		$align = $value = $matches = array();
		foreach($_value as $val) {
			if (preg_match('/^(\s+)?(.+?)(\s+)?$/', $val, $matches)) {
				$align[] =($matches[1] != '') ?
					((isset($matches[3]) && $matches[3] != '') ?
						' style="text-align:center"' :
						' style="text-align:right"'
					) : '';
				$value[] = $matches[2];
			} else {
				$align[] = '';
				$value[] = $val;
			}
		}
		$this->col = count($value);
		$colspan = array();
		foreach ($value as $val)
			$colspan[] = ($val == '==') ? 0 : 1;
		$str = '';
		$count = count($value);
		for ($i = 0; $i < $count; $i++) {
			if ($colspan[$i]) {
				while ($i + $colspan[$i] < $count && $value[$i + $colspan[$i]] == '==')
					$colspan[$i]++;
				$colspan[$i] = ($colspan[$i] > 1) ? ' colspan="' . $colspan[$i] . '"' : '';
				$str .= '<td' . $align[$i] . $colspan[$i] . '>' . make_link($value[$i]) . '</td>';
			}
		}
		$this->elements[] = $str;
	}

	public function canContain($obj)
	{
		return is_a($obj, 'YTable') && ($obj->col == $this->col);
	}

	public function insert($obj)
	{
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	public function toString()
	{
		$rows = '';
		foreach ($this->elements as $str)
			$rows .= "\n" . '<tr class="style_tr">' . $str . '</tr>' . "\n";
		$rows = $this->wrap($rows, 'table', ' class="style_table" cellspacing="1" border="0"');
		return $this->wrap($rows, 'div', ' class="ie5"');
	}
}

// ' 'Space-beginning sentence
// ' 'Space-beginning sentence
// ' 'Space-beginning sentence
class Pre extends Element
{
	public function __construct($root, $text)
	{
		global $preformat_ltrim;
		parent::__construct();
		$this->elements[] = htmlsc(
			(! $preformat_ltrim || $text == '' || $text[0] != ' ') ? $text : substr($text, 1));
	}

	public function canContain($obj)
	{
		return is_a($obj, 'Pre');
	}

	public function insert($obj)
	{
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	public function toString()
	{
		$str = join("\n", $this->elements);
		if (count($this->elements) == 1) {
			return $this->wrap(join("\n", $this->elements), 'pre');
		} else {
			return "\n```\n$str\n```\n";
		}
	}
}

// Block plugin: #something (started with '#')
class Div extends Element
{
	public $name;
	public $param;

	public function __construct($out)
	{
		parent::__construct();
		list(, $this->name, $this->param) = array_pad($out, 3, '');
	}

	public function canContain($obj)
	{
		return FALSE;
	}

	public function toString()
	{
		// Call #plugin

		// return do_plugin_convert($this->name, $this->param);

		if ($this->name == 'pre') {
			return "```$this->param```";
		} elseif ($this->name == 'color') {
			return "<font color>$this->name</font>";
		} elseif ($this->name == 'size') {
			return "<font size>$this->name</font>";
		} elseif ($this->name == 'lsx') {
			return "\$lsx(./)";
		} elseif ($this->name == 'table_edit2') {
			$this->param = str_replace(",table_mod=close\r", "", $this->param);
			return convert_html(str_replace("\r", "\r\n", $this->param));
		} else {
			return "@@@@ $this->name プラグインには対応していません。適宜対応してください @@@@";
		}
	}
}

// LEFT:/CENTER:/RIGHT:
class Align extends Element
{
	public $align;

	public function __construct($align)
	{
		parent::__construct();
		$this->align = $align;
	}

	public function canContain($obj)
	{
		return is_a($obj, 'Inline');
	}

	public function toString()
	{
		return $this->wrap(parent::toString(), 'div', ' style="text-align:' . $this->align . '"');
	}
}

// Body
class Body extends Element
{
	public $id;
	public $count = 0;
	public $contents;
	public $contents_last;
	public $classes = array(
		'-' => 'UList',
		'+' => 'OList',
		'>' => 'BQuote',
		'<' => 'BQuote');
	public $factories = array(
		':' => 'DList',
		'|' => 'Table',
		',' => 'YTable',
		'#' => 'Div');

	public function __construct($id)
	{
		$this->id            = $id;
		$this->contents      = new Element();
		$this->contents_last = & $this->contents;
		parent::__construct();
	}

	public function parse(& $lines)
	{
		$this->last = & $this;
		$matches = array();

		while (! empty($lines)) {
			$line = array_shift($lines);

			// Escape comments
			if (substr($line, 0, 2) == '//') continue;

			if (preg_match('/^(LEFT|CENTER|RIGHT):(.*)$/', $line, $matches)) {
				// <div style="text-align:...">
				$this->last = $this->last->add(new Align(strtolower($matches[1])));
				if ($matches[2] == '') continue;
				$line = $matches[2];
			}

			$line = rtrim($line, "\r\n");

			// Empty
			if ($line == '') {
				$this->last = $this->last->add(Factory_Inline($line));
				continue;
			}

			// Horizontal Rule
			if (substr($line, 0, 4) == '----') {
				$this->insert(Factory_Inline('---'));
				continue;
			}

			// Multiline-enabled block plugin
			// if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			//     preg_match('/^#[^{]+(\{\{+)\s*$/', $line, $matches)) {
			if (preg_match('/^#[^{]+(\{\{+)\s*$/', $line, $matches)) {
				$len = strlen($matches[1]);
				$line .= "\r"; // Delimiter
				while (! empty($lines)) {
					$next_line = preg_replace("/[\r\n]*$/", '', array_shift($lines));
					if (preg_match('/\}{' . $len . '}/', $next_line)) {
						$line .= $next_line;
						break;
					} else {
						$line .= $next_line .= "\r"; // Delimiter
					}
				}
			}

			// The first character
			$head = $line[0];

			// Heading
			if ($head == '*') {
				$this->insert(new Heading($this, $line));
				continue;
			}

			// Pre
			if ($head == ' ' || $head == "\t") {
				$this->last = $this->last->add(new Pre($this, $line));
				continue;
			}

			// Line Break
			if (substr($line, -1) == '~')
				$line = substr($line, 0, -1) . "\r";

			// Other Character
			if (isset($this->classes[$head])) {
				$classname  = $this->classes[$head];
				$this->last = $this->last->add(new $classname($this, $line));
				continue;
			}

			// Other Character
			if (isset($this->factories[$head])) {
				$factoryname = 'Factory_' . $this->factories[$head];
				$this->last  = $this->last->add($factoryname($this, $line));
				continue;
			}

			// Default
			$this->last = $this->last->add(Factory_Inline($line));
		}
	}

	public function getAnchor($text, $level)
	{
		global $top, $_symbol_anchor;

		// Heading id (auto-generated)
		$autoid = 'content_' . $this->id . '_' . $this->count;
		$this->count++;

		// Heading id (specified by users)
		$id = make_heading($text, FALSE); // Cut fixed-anchor from $text
		if ($id == '') {
			// Not specified
			$id     = & $autoid;
			$anchor = '';
		} else {
			// $anchor = ' &aname(' . $id . ',super,full){' . $_symbol_anchor . '};';
			$anchor = '';
		}

		$text = ' ' . $text;

		// Add 'page contents' link to its heading
		$this->contents_last = $this->contents_last->add(new Contents_UList($text, $level, $id));

		// Add heding
		return array($text . $anchor, $this->count > 1 ? "\n" . $top : '', $autoid);
	}

	public function insert($obj)
	{
		if (is_a($obj, 'Inline')) $obj = $obj->toPara();
		return parent::insert($obj);
	}

	public function toString()
	{
		global $vars;

		$text = parent::toString();

		// #contents
		// $text = preg_replace_callback('/<#_contents_>/',
		// 	array(& $this, 'replace_contents'), $text);

		return $text . "\n";
	}

	public function replace_contents($arr)
	{
		$contents  = '<div class="contents">' . "\n" .
				'<a id="contents_' . $this->id . '"></a>' . "\n" .
				$this->contents->toString() . "\n" .
				'</div>' . "\n";
		return $contents;
	}
}

class Contents_UList extends ListContainer
{
	public function __construct($text, $level, $id)
	{
		// Reformatting $text
		// A line started with "\n" means "preformatted" ... X(
		make_heading($text);
		$text = "\n" . '<a href="#' . $id . '">' . $text . '</a>' . "\n";
		parent::__construct('ul', 'li', '-', str_repeat('-', $level));
		$this->insert(Factory_Inline($text));
	}

	public function setParent($parent)
	{
		global $_list_pad_str;

		parent::setParent($parent);
		$step   = $this->level;
		$margin = $this->left_margin;
		if (isset($parent->parent) && is_a($parent->parent, 'ListContainer')) {
			$step  -= $parent->parent->level;
			$margin = 0;
		}
		$margin += $this->margin * ($step == $this->level ? 1 : $step);
		$this->style = sprintf($_list_pad_str, $this->level, $margin, $margin);
	}
}
?>

<?php
// Token-based extractor for WP i18n calls.
$funcs = ['__','_e','esc_html__','esc_html_e','esc_attr__','esc_attr_e','_x','_ex','esc_attr_x','esc_html_x','_n','_nx'];
$files = array_merge(glob('includes/*.php'), ['wp-booking-simple.php']);
$entries = []; // key => ['msgid'=>, 'plural'=>, 'context'=>]
foreach ($files as $f) {
	$tokens = token_get_all(file_get_contents($f));
	$n = count($tokens);
	for ($i=0; $i<$n; $i++) {
		$t = $tokens[$i];
		if (is_array($t) && $t[0]===T_STRING && in_array($t[1],$funcs,true)) {
			// next non-whitespace must be '('
			$j=$i+1; while($j<$n && is_array($tokens[$j]) && $tokens[$j][0]===T_WHITESPACE) $j++;
			if (!($j<$n && $tokens[$j]==='(')) continue;
			// collect string literal args at top paren depth
			$depth=0; $args=[]; $cur=null;
			for ($k=$j; $k<$n; $k++) {
				$tk=$tokens[$k];
				if ($tk==='(') { $depth++; continue; }
				if ($tk===')') { $depth--; if($depth===0) break; continue; }
				if ($depth===1 && $tk===',') { $args[]=$cur; $cur=null; continue; }
				if ($depth===1 && is_array($tk) && $tk[0]===T_CONSTANT_ENCAPSED_STRING) {
					if ($cur===null) $cur = stripcslashes(substr($tk[1],1,-1)); // strip quotes
				}
			}
			$args[]=$cur;
			$fn=$t[1];
			$e=['msgid'=>null,'plural'=>null,'context'=>null];
			if ($fn==='_n') { $e['msgid']=$args[0]??null; $e['plural']=$args[1]??null; }
			elseif ($fn==='_nx') { $e['msgid']=$args[0]??null; $e['plural']=$args[1]??null; $e['context']=$args[3]??null; }
			elseif (in_array($fn,['_x','_ex','esc_attr_x','esc_html_x'],true)) { $e['msgid']=$args[0]??null; $e['context']=$args[1]??null; }
			else { $e['msgid']=$args[0]??null; }
			if ($e['msgid']===null || $e['msgid']==='') continue;
			$key=$e['context'].chr(4).$e['msgid'];
			$entries[$key]=$e;
		}
	}
}
ksort($entries);
echo json_encode(array_values($entries), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

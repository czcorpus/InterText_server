<?php
/*  Copyright (c) 2010-2017 Pavel Vondřička (Pavel.Vondricka@korpus.cz)
 *  Copyright (c) 2010-2017 Charles University in Prague, Faculty of Arts,
 *                          Institute of the Czech National Corpus
 *
 *  This file is part of InterText Server.
 *
 *  InterText Server is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  InterText Server is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with InterText Server.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require 'init.php';
require 'header.php';
?>
<div id="help">

<h1>Návod k systému InterText</h1>

<a name="almanager"></a>
<h2>Správce zarovnání</h2>

<p>Správce zarovnání je tabulka s výpisem všech zarovnání dostupných pro uživatele. (Administrátor si může nechat zobrazit jen zarovnání určitého textu (jazykové verze) pomocí <a href="#textmanager">správce textů</a>.)</p>

<ul>
<li>První sloupec slouží k výběru zarovnání pro hromadné změny (viz <a href="#batch">níže</a>).</li>
<li>Druhý sloupec tabulky udává název textu, společný pro všechny jeho jazykové verze.</li>
<li>Třetí sloupec obsahuje název (či celý seznam) zarovnání, ukazující které verze jsou vůči sobě zarovnány (v podobě "verze1 &lt;=&gt; verze2"). Kliknutím na název se otevře <a href="#aleditor">editor zarovnání</a>.</li>
<li>(Administrátorovi se za názvem zarovnání zároveň zobrazí tlačítko <img src="icons/swap.png" alt="[S]"/>, umožňující prohodit textové verze (pravou a levou stranu) v zarovnání, a dále přepínač označený ikonkou <img src="icons/merge.png" alt="merge"/>, povolující opravy špatného dělení vět v centrální (pivotní) textové verzi - tedy v případě projektu InterCorp dělení vět v české verzi textu. Toto oprávnění je ve výchozím nastavení vždy zakázáno a musí být administrátorem explicitně povoleno pro každé jednotlivé zarovnání zvlášť. Při přidání každého nového zarovnání mezi verzemi daného textu se navíc toto povolení samovolně anuluje. Předpokládá se, že je nežádoucí měnit rozdělení vět u textů již zarovnaných vůči nějaké další verzi, i když systém automaticky hlídá, aby nedošlo k porušení konzistence žádného zarovnání přítomného v systému. Není možné vyloučit možnost, že existují další zarovnání daného textu, která nejsou v systému momentálně přítomná.)</li>
<li>Koordinátoři a administrátoři mohou v dalším sloupci měnit aktuální nastavení editora zarovnání. Pouze zde momentálně nastavený uživatel má právo zarovnání editovat. Tak nemůže dojít k situaci, kdy by dva lidé měli právo manipulovat současně s jedním zarovnáním. V současné verzi však může do zarovnání kdykoliv zasahovat i koordinátor (a administrátoři).</li>
<li>Za volbou editora je také přepínač označený ikonkou <img src="icons/document-edit.png" alt="edit"/>, povolující editaci textu. Toto nastavení se vztahuje na obě jazykové verze a současně na opravy chyb v textu, jakož i na opravy špatného rozdělení vět. Opravy rozdělení vět v centrálním (tj. českém) textu ovšem musí být dodatečně povoleny administrátorem (viz výše), jinak budou nadále blokovány.</li>
<li>Koordinátoři a administrátoři mohou v dalším sloupci tabulky zvolit uživatele, jemuž chtějí předat dané zarovnání do správy. Po kliknutí na jméno aktuální zodpovědné osoby se zobrazí výběr koordinátorů a administrátorů, jimž je možné zarovnání předat. Předání se provede prostým výběrem jména uživatele a potvrzením volby. Vzhledem k tomu, že koordinátoři mohou přistupovat pouze k těm zarovnáním, za něž mají sami zodpovědnost, po předání jiné osobě se pro ně stane dané zarovnání nedostupným (ani pro čtení!) a nemohou tak už sami tuto akci vzít zpět. Zarovnání je od této chvíle v zcela kompentenci nového koordinátora. Administrátoři mají stálý přístup ke všem textům a zarovnáním a mohou tedy předávat zodpovědnost mezi koordinátory libovolně.</li>
<li>V posledním sloupci (administrátorům v předposledním) se zobrazuje stav zarovnání (status). Zarovnání, na nichž se pracuje, mají status "open". Zarovnání v jakémkoliv jiném stavu jsou přístupná pouze ke čtení, ale není možné do nich jakkoliv zasahovat. Koordinátor po dokončení práce na zarovnání nastaví stav na "finished", čímž dá najevo, že zarovnání je hotovo a připraveno k zařazení do korpusu. Po zařazení do korpusu administrátor nastaví stav na "closed". Status "finished" může ještě koordinátor sám odvolat a vrátit zpět do stavu "open". To už nelze udělat s textem uzavřeným ("closed"). Čtvrtý možný stav je "blocked", který slouží administrátorovi k zamknutí zarovnání z nějakých jiných důvodů. Ani ten nemohou koordinátoři sami změnit. Poslední možný stav je stav zamknutí pro vzdáleného editora ("remote editor"), který značí, že zarovnání zpracovává editor externě pomocí nativní aplikace <i>InterText editor</i> a zarovnání tak může být kdykoliv přepsáno při synchronizaci. Tento stav se nastavuje automaticky, pokud si editor zarovnání stáhne do svého počítače k domácí práci "off-line" a zrušit jej může opět jen on sám, anebo (násilím) administrátor.</li>
<li>(Administrátorům se v posledním sloupci objevuje ikona <img src="icons/edit-delete-shred.png" alt="[DELETE]" /> umožňující dané zarovnání zcela smazat.)</li>
</ul>

<a name="batch"></a>
<h3>Provádění hromadných změn</h3>
<p>Většina výše zmíněných změn může být prováděna i hromadně na více zarovnáních současně. Zarovnání lze vybrat zaškrtnutím v prvním sloupci patřičné řádky. Symboly <img src="icons/add.png" alt="[+]" title="select all"/> a <img src="icons/remove.png" alt="[-]" title="unselect all"/> lze nechat vybrat (zaškrtnout) či naopak zrušit výběr u všech zarovnání na dané stránce najednou. Výběr zarovnání na různých stránkách není možný (místo toho použijte filtr a/nebo změňte počet zobrazovaných zarovnání na stránce!). Aplikace změn se pak řídí těmito prvidly:</p>

<ul>
<li>Pokud není vybráno žádné zarovnání, každá změna se aplikuje vždy jen na to zarovnání, v jehož řádku je aktivována.</li>
<li>Pokud je změna aktivována u jednoho (kteréhokoliv) z vybraných zarovnání, je uživateli nabídnuto, zda ji chce aplikovat i na všechny ostatní vybraná zarovnání. Pokud odmítne, je změna aplikována opět jen na dotyčné zarovnání samostatně.</li>
<li>Pokud jsou nějaká zarovnání vybrána, ale změna je aktivována u zarovnání, které není součástí výběru, provede se změna opět jen u něj.</li>
<li>Pokud je nějaká změna prováděna hromadně, ale u některého z vybraných zarovnání na ni uživatel nemá oprávnění nebo není realizovatelná z jiného důvodu, bude provedena jen u ostatních vybraných zarovnání a uživatel bude informován o všech neúspěšných pokusech o změnu.</li>
</ul>

<p>Hromadně lze provádět následující změny: prohození stran (textových verzí) v zarovnání, povolení či zákaz oprav špatného dělení vět v centrální (pivotní) verzi, povolení či zákaz editace textu obecně, změna editora, změna zodpovědné osoby (koordinátora) a změna stavu zarovnání. Hromadné mazání zarovnání není možné.</p>

<h3>Stránkování seznamu zarovnání</h3>
<p>Seznam se objevuje po stránkách, mezi nimiž lze listovat pomocí odkazů "&lt;&lt; previous" a "next &gt;&gt;". Počet zarovnání zobrazených na jedné stránce lze nastavit v horní liště selektorem "show". Lze volit mezi hodnotami 10, 20, 30, 50 a 100 zarovnání na stránku, nebo vypnout stránkování a nechat si zobrazit vždy celý seznam (volba "all"). 
</p>

<h3>Řazení seznamu zarovnání</h3>
<p>Tabulka seznamu zarovnání je ve výchozím nastavení řazena primárně podle názvu textu (abecedně a vzestupně) a sekundárně podle názvu levé verze textu v zarovnání. Řazení je možné měnit pomocí šipek v hlavičce tabulky. Primární řazení podle názvu textu je můžné aktivovat (ať už vzestupně či sestupně abecedně) i zcela deaktivovat (opětovným kliknutím na šipku zvoleného směru řazení) nezávisle na řazení sekundárním, ale pouze v kombinaci s tříděním podle názvu levé či pravé verze textu v zarovnání. Deaktivací primárního řazení podle názvu textu se zarovnání budou řadit pouze podle názvu vybrané verze. Volby řazení podle jména editora, zodpovědné osoby nebo stavu jsou vždy primární, a proto automaticky deaktivují řazení (seskupování) podle názvu textu.</p>

<h3>Filtrování seznamu zarovnání</h3>
<p>V první řádce tabulky jsou selektory a textové pole, jimiž je možné specifikovat kritéria výběru jen některých zarovnání. Stisknutím tlačítka "Filter!" na konci řádky se aplikuje filtr a zobrazí se seznam pouze se zarovnáními vyhovujícími zadaným kritériím. Pokud je v horní liště nastaven mód filtru na "auto", změny filtru se projeví automaticky ihned pře změně některého ze selektorů. V textovém poli je třeba zadaný řetězec aktivovat klávesou ENTER.</p>

<a name="newalign"></a>
<h3>Vytvoření nového zarovnání</h3>
<?php if ($USER['type']==$USER_ADMIN) { ?>
<p>Administrátor může vytvářet nová zarovnání pomocí volby v menu, která se zobrazí ve správci zarovnání, pokud byl zvolen pouze výpis zarovnání pro jednu specifickou textovou verzi (tu lze vybrat pomocí <a href="#textmanager">správce textů</a>). Zarovnání je také možné vytvářet a importovat pomocí CLI nástroje <em>align</em> (nápověda se zobrazí po spuštění skriptu bez parametrů) nebo při importu textů nástrojem <em>import</em> (viz <a href="#newtext">přidávání nových textů</a>).</p>

<ul>
<li>Po zobrazení formuláře pro vytvoření nového zarovnání je třeba vybrat textovou verzi, vůči níž bude zvolená verze zarovnávána</li>
<li>Zarovnání je možno importovat z připraveného souboru ve formátu TEI XML, obsahujícího element <em>linkGrp</em>, odkazující na odpovídající verze textů (v libovolném směru / pořadí, ovšem hodnoty atributů <em>toDoc</em> a <em>fromDoc</em> musí mít podobu <em>název_textu.název_verze.libovolná_koncovka</em> odpovídající názvům textu a verze, jak jsou pojmenovány v systému InterText) a seznam elementů <em>link</em> s atributy <em>xtargets</em> (seznam identifikátorů elementů tvořících jeden segment, oddělených mezerou, nejdříve pro cílový text <em>toDoc</em> a pak, oddělený středníkem, pro výchozí text <em>srcDoc</em>) a případně <em>status</em>.</li>
<li>Pokud některé segmenty v importovaném zarovnání nemají nastavený stav (<em>status</em>) nebo mají nastavený stav systému neznámý, nastaví se pro ně při importu stav zvolený ve formuláři jako výchozí stav (<em>default status</em>).</li>
<li>Nastavení výběru a profilu automatického zarovnání se použije na všechny elementy, které nebyly obsažené v importovaném zarovnání. Pokud nebylo nic importováno, bude automatické zarovnání použito na celý text.)</li>
<?php } else { ?>
<p>Nová zarovnání může vytvářet pouze administrátor.</p>

<ul>
<?php } ?>
<li>Momentálně podporované metody automatického zarovnání jsou: a) <em>hunalign</em> - jazykově nezávislý stochastický zarovnávač, kterému lze napomoci jazykově specifickým slovníkem; b) <em>TCA2</em> - jazykově závislý zarovnávač, vyžadující profil (základní slovníček a nastavení parametrů přibližných poměrů délky textů, aj.) specifický pro daný jazykový pár; c) nouzový zarovnávač 1:1 (<em>plain alignment</em>, spojí elementy pouze v poměru 1:1 a případný přebytek elementů na jedné straně v poměru 1:0, nastaví stav pouze na "nepotvrzený"). Všechny automatické zarovnávače (kromě nouzového) nastaví stav segmentů na "automaticky zarovnaný".</li>

<li>Volba zabraňující uzavření logu po skončení procesu vytváření nového zarovnání umožňuje zachovat stavovou stránku se záznamem (logem) průběhu celého procesu. Uživatel se pak musí vrátit zpět ručně pomocí odkazu v menu. Pokud není tato volba zaškrtnuta, systém se po ukončení procesu automaticky vrátí do výchozího stavu.</li>
</ul>

<p>Proces automatického zarovnávání může trvat několik minut až řádově desítky minut (u <em>TCA2</em>, v případě dlouhých textů). Průběh zarovnání pomocí zarovnávače <em>hunalign</em> není zobrazován na ukazateli průběhu, jsou pouze zobrazovány zprávy o aktuální fázi procesu.</p>

<a name="aleditor"></a>
<h2>Editor zarovnání</h2>

<p>Editor zarovnání sestává z <em>horní lišty</em>, <em>tabulky editoru</em>, <em>dolní lišty</em> a <em>stavové řádky</em>. Všechny operace v editoru jsou prováděny okamžitě (tj. jsou ihned zaneseny do databáze na serveru). To znamená, že práci lze kdykoliv ukončit či přerušit, aniž by bylo třeba cokoliv ukládat nebo zvláštním způsobem ukončovat či uzavírat. Na druhou stranu ovšem neexistuje způsob, jak jednou provedené změny vrátit zpět jinak, než ručně, opačnou operací. Proto si dobře rozmyslete zvláště zásahy, které vyžadují dodatečné potvrzení. Pokud k systému přistupujete z veřejně přístupného počítače nechráněného heslem, doporučuje se po ukončení práce odhlásit ze systému InterText, aby příští návštěvník nemohl svévolně "pokračovat" ve vaší práci (samotné ukončení prohlížeče nemusí být dostatečně bezpečné).
</p>

<h3>Tabulka editoru</h3>

<ul>
<li>Každá stránka zobrazuje pouze část paralelních textů. Stránkováním se lze posouvat po textu dopředu a dozadu. Jak velká část se zobrazí najednou na jedné stránce, lze volit v nastavení na horní liště. Výchozí nastavení je dvacet pozic (segmentů) na stránku.</li>
<li>Každý řádek tabulky editoru značí jednu pozici, neboli segment, který seskupuje odpovídající si skupiny vět (elementů) v obou paralelních textech (jazykových verzích).</li>
<li>Levý sloupec obsahuje pořadové číslo pozice (segmentu). Kliknutím na toto číslo je možné posunout výchozí pracovní pozici editoru (kurzor) na danou pozici - tj. posunout začátek viditelné stránky k dané pozici. (Blíže viz <em>volba pracovního módu</em> na <a href="#topbar">horní liště</a>.)</li>
<li>Vedle čísla pozice je sloupec značek (záložek). Kliknutím na symbol <img src="icons/nomark.png" alt="0"/> se daná pozice označí značkou <img src="icons/mark.png" alt="1"/> a je možné ji pak vyhledat prostřednictvím <a href="#topbar">horní ovládací lišty</a>. Opětovným kliknutím se značka opět smaže.</li>
<li>Centrální dva sloupce zobrazují v každé řádce (pozici) skupiny sobě odpovídajích vět v obou textech. Každá jednotlivá věta začíná modrým trojůhelníkem. Dvojitý trojůhelník značí začátek nového odstavce. Při podržení kurzoru myši nad textem dané věty se zobrazí v bublině její aktuální ID v rámci dokumentu.</li>
<li>Ikona <img src="icons/changelog.png" alt=[ch]/> předchází všechny elementy, které byly změněny (editovány, spojeny či rozděleny). Kliknutím na ni je možno otevřít seznam provedených změn (pro další podrobnosti viz dále sekci "Editace textu").</li>
<li>U středu se nachází ovládací tlačítka pro editaci zarovnání: každý text má na své straně čtyři tlačítka v podobě zelených šipek, které slouží k přesunování každého z textů mezi pozicemi (viz níže).</li>
<li>Mezi oběma texty se navíc nachází úzký sloupeček se dvěma modrými šipkami, které slouží k posunování obou textů současně (viz níže).</li>
<li>V pravém sloupci je značka stavu každé pozice. Rozlišují se tři různé stavy: 1. schválený segment (označený zeleným háčkem <img src="icons/dialog-ok-apply.png" alt="manual"/>), 2. segment sestavený automatickým zarovnávačem, nezkontrolovaný (ozubené kolečko <img src="icons/automatic.png" alt="automatic alignment"/>), 3. segment sestavený nahodile, neznámý či jinak nepotvrzený (varovný trojůhelník <img src="icons/status_unknown.png" alt="plain link"/>).</li>
<li>Kliknutím na značku stavu v pravém sloupci lze manuálně měnit (přepínat) nastavení stavu jednotlivých segmentů mezi stavy 1 a 3, v běžné editaci to ovšem není třeba dělat - tato funkce slouží spíše pro dodatečné kontroly a značení problematických míst.</li>
</ul>

<p><b>Editace zarovnání</b></p>

<ul>
<li><img src="icons/arrow-up.png" alt="element up"/> Jednoduchá zelená šipka vzhůru přehodí první větu ze současné pozice (segmentu) na pozici předchozí (o řádek výše). Zbytek textu zůstane nedotčen.</li>
<li><img src="icons/arrow-down.png" alt="element down"/> Jednoduchá zelená šipka dolů přehodí poslední větu ze současné pozice (segmentu) na následující pozici (o řádek níže). Zbytek textu zůstane nedotčen.</li>
<li><img src="icons/arrow-up-double.png" alt="text up"/> Dvojitá zelená šipka vzhůru posune celý text na jedné straně ze současné pozice (a všech následujících) o pozici (řádek) výše. Všechny věty daného textu se tedy přesunou (přidají) z dané pozice (segmentu, řádky) do pozice předcházející, a celý následující text na jedné straně se současně posune o uvolněnou pozici výše.</li>
<li><img src="icons/arrow-down-double.png" alt="text down"/> Dvojitá zelená šipka dolů odsune celý text na jedná straně ze současné pozice (a všech následujících) o pozici (řádek) níže. Na dané pozici se tedy vytvoří na jedné straně prázdný řádek.</li> 
<li><img src="icons/go-up.png" alt="both up"> Modrá šipka vzhůru posune oba texty od současné pozice o řádek výše. To znamená, že současný segment se zcela sloučí se segmentem předchozím. Stisknutí modré šipky odpovídá použití dvojitých zelených šipek na obou stranách současně.</li>
<li><img src="icons/go-down.png" alt="both down"> Modrá šipka dolů posune oba texty od současné pozice o řádek níže. To znamená, že současný segment se zcela vyprázdní (vznikne prázdná pozice / řádek) a zbytek obou textů se odsune. Stisknutí modré šipky odpovídá použití dvojitých zelených šipek na obou stranách současně.</li>
<li><img src="icons/arrow.png" alt="&gt;"/> Kliknutím na modrý trojúhelníček na začátku každé věty lze danou větu (a celý následující text) posunout na pozici zadanou v dialogu, který se objeví (zadejte číslo cílové pozice / segmentu). Tímto způsobem lze posunovat text na jedné straně o několik pozic najednou, aniž by bylo třeba opakovaně klikat na dvojité zelené šipky. Tuto funkci lze s výhodou využít, pokud v jednom z textů chybí celý odstavec či kapitola. (Pozn.: Systém pochopitelně nedovolí posunout text směrem vzhůru na pozice již obsazené předchozími větami, ale pouze na prázdné pozice.) Při potvrzením prázdného dialogu (bez vyplnění čísla pozice) je dále nabídnuto vložení nebo smazání zlomu odstavců před danou větou - viz níže.</li>
</ul>

<p><b>Editace textu (oprava překlepů a chyb)</b></p>

<p>Systém umožňuje opravovat chyby a překlepy v textech, pokud to povolí koordinátor (administrátor). V tom případě je možné dvojitým kliknutím otevřít každou jednotlivou větu k editaci a po ruční opravě text uložit (nebo editaci zrušit a vrátit text do původního stavu).</p>
<p>Každá změna textu nějakého elementu je zaznamenána v historii změn (changelogu), kterou je možné zobrazit kliknutím na ikonu <img src="icons/changelog.png" alt=[ch]/>, jež se objevuje před každým elementem, který byl změněn (editován, rozdělen nebo spojen), nebo nechat permanetně zobrazovat u všech elementů přepnutím stejné ikony v horní liště. Pod stávajícím textem se objeví tabulka zobrazující zpětně chronologicky vždy typ změny se jménem uživatale a datem provedené změny, následovaný archivovaným stavem textu bezprostředně předcházejícím dané změně. Odspoda nahoru lze tedy sledovat jak, kdy a kým byl text postupně měněn. Tabulka může obsahovat i vnořené tabulky s historiemi připojených (a tím již smazaných) elementů. Kliknutím na text některé z předchozích verzí může být (po dodatečném potvrzení) stávající text elementu nahrazen starší verzí jeho obsahu. (Nebude však už provedeno žádné případné spojování rozdělených elementů ani dělení elementů dříve spojených - nejedná se tedy o možnost automatického vracení strukturních změn.)</p>
<p><b>POZOR!</b> Tato funkce také nesmí být svévolně používána k přesunování slov mezi větami (například špatně rozdělenými). Viz dále: "oprava chybného dělení vět".</p>

<p><b>Oprava chybného dělení vět a odstavců</b></p>

<p>Pokud je povolena editace textu, může editor také opravovat chybně rozdělené věty. (Opravy dělení v českých textech musí povolit explicitně administrátor - pokud je již český text zarovnán s jinými jazyky, nelze svévolně jeho dělení na věty měnit.)</p>
<ul>
<li>Pokud je třeba rozdělit existující "větu", která omylem slučuje věty dvě (nebo více), je třeba otevřít daný text k editaci a mezi jednotlivé věty vložit prázdný řádek (tj. 2x ENTER). Po uložení se dotyčný text rozdělí na věty podle zadání. Ty je pak možné samostatně přesunovat mezi segmenty.</li>
<li>Spojit dvě omylem rozdělené věty lze použitím tlačítka <img src="icons/merge.png" alt="merge"/>, které se nachází na konci každé věty, která není poslední větou na dané pozici (v daném segmentu). Spojovat lze pouze věty, které jsou součástí stejného segmentu (ve všech zarovnáních daného textu). Použitím této funkce se daná "věta" spojí s "větou" následující do jedné jediné. Systém nejdříve požádá o potvrzení, neboť tato operace je zcela nevratná v případě, kdy se mezi oběma chybně dělenými větami nachází například i zlom odstavce - ten bude nevratně smazán a nelze ho obnovit ani opětovným rozdělením sloučených vět.</li>
<li>Zlom odstavce je možné vložit nebo smazat po kliknutí na modrý (dvojitý) trojúhelníček (šipku) na začátku patřičné věty. Nejprve se objeví dialog, nabízející možnost přesunu věty na novou pozici (viz výše). Potvrzením ("OK") prázdného dialogu se otevře druhý dialog, který již nabídne vložení nebo zrušení (podle aktuálního stavu) předělu odstavců bezprostředně před zvolenou větou.</li>
</ul>

<a name="topbar"></a>
<h3>Horní ovládací lišta editoru, nastavení a módy editace</h3>

<p>Horní lišta obsahuje na pravém a levém okraji modré šipky ke stránkování na předchozí a následující stránku v textu. (Pro posun o stránku vzad, resp. o stránku vpřed, lze též použít funkční klávesy F7, resp. F8. Některé prohlížeče se však budou dožadovat povolení této funkcionality.) Navíc se v jejím středu nacházejí další ovládací tlačítka a přepínače:</p>

<ul>
<li>Modré šipky se zarážkou (<img src="icons/go-first.png" alt="go to start" /> a <img src="icons/go-last.png" alt="go to end" />) slouží k přesunu na první a poslední stránku (začátek a konec) textu (zarovnání).</li>
<li><img src="icons/go-up.png" alt="[LIST]" /> Šipka vzhůru je cestou ven z editoru na seznam všech zarovnání (viz <a href="#almanager">správce zarovnání</a>).</li>
<li><img src="icons/help-contents.png" alt="help" /> Znak knihy s otazníkem odkazuje na tento návod.</li>
<li><img src="icons/document-save.png" alt="[EXPORT]" /> Ikona diskety otevře ve spodní části panelu zvláštní panel k exportu obou textů i samotného zarovnání a jejich stažení (uložení) na lokální počítač, například pro další osobní použítí v ParaConku či jiném softwaru. Vybráním formátu pro export z roletového výběru se pak aktivuje export daného textu (či zarovnání). Momentálně podporované formáty exportu jsou: čistý XML text (s obyčejnými, krátkými identifikátory); čistý XML text s dlouhými identifikátory projektu InterCorp pro korpusový manažer Manatee; čistý XML text s dlouhými identifikátory projektu ECPC, založenými na původním názvu souboru; XML text s atributy "corresp" u každé věty, odkazujícími na odpovídající věty v paralelním textu dle současného zarovnání; text se segmenty, jak je používá ParaConc. Export zarovnání je možný pouze v podobě XML, buď s obyčejnými (krátkými) nebo dlouhými identifikátory. Všechny texty jsou exportovány v kódování UTF-8.</li>
<li><a name="realign"></a><img src="icons/automatic.png" alt="[REALIGN]" /> Symbol ozubeného kola slouží k opětovnému použití automatického zarovnání na celý zbytek textu od první pozice, která je označena (stavem) jako nepotvrzená (i když za ní následují další potvrzené pozice!). Celé stávající zarovnání následující od této pozice až do konce se tedy zruší a přeskupí podle nových propočtů automatického zarovnávače. Před provedením akce systém požádá o potvrzení. (Pokud to administrátor povolil, může editor v tuto chvíli i změnit metodu automatického zarovnávání. Pro podrobnosti viz sekci <a href="#newalign">vytvoření nového zarovnání</a>.) Tuto funkci lze využít na příklad v situaci, kdy v jednom z textů (obvykle v překladu) chybí dlouhý odstavec nebo dokonce celá kapitola, což způsobilo zmatek v počátečním automatickém zarovnání: po ručním srovnání a schválení celé mezery v textu je možné nechat znovu automaticky přerovnat zbytek textu za mezerou a ušetřit si tak zdlouhavé ruční opravy dalších zbytečných chyb v celém zbytku zarovnání. Pokud překlad už neobsahuje žádné další mezery (větší výpustky), může automatický zarovnávač jeho zbytek už přerovnat s mnohem větším úspěchem a tudíž menší nutností dalších ručních oprav. (Tato funkce může být zablokována administrátorem.)</li>
<li><img src="icons/layer-visible-on.png" alt="[C]" /> Ikona oka je přepínačem skrývání ovládacích tlačítek (šipek) v editoru. Jeho stisknutím lze zneviditelnit všechna ovládací tlačítka v tabulce editoru, aby se text lépe četl. Tlačítka se pak objevují jen dle potřeby, pokud se v jejich prostoru vyskytne kurzor myši. Opětovným stiskem se všechna tlačítka opět zviditelní natrvalo.</li>
<li>Ikona <img src="icons/changelog.png" alt=[ch]/> slouží k přepínání trvalého zobrazení historie všech provedených změn textu. (viz sekci <i>Editace textu</i> výše)</li>
<li>Tlačítko "<span class="non11">non-1:1</span>" přepíná barevné zvyraznění pozic (segmentů) obsahujících věty v jiném poměru než 1:1. Tak lze rychle opticky zvýraznit nejběžnější problematická místa, kde se mohou vyskytovat chyby. Jedním stiskem se zvýraznění vypne, druhým opět zapne.</li>
<li><em>Přepínač módu zarovnání</em> volí způsob, jak bude editor reagovat na každou změnu v zarovnání (každé použití šipek):
<ul>
	<li>Manuální módy ("manual status update") jsou určeny k drobným opravám nebo kontrole již hotového a schváleného zarovnání. V těchto módech se neprovádí žádné automatické nastavení stavu jednotlivých segmentů.</li>
	<li>Módy automatické aktualizace stavu ("auto update status") jsou určeny pro uživatele, kteří si přejí, aby editor automaticky změnil při každém zásahu do zarovnání stav aktuálně změněného a všech předcházejících segmentů na "schválený" (předpokládá se, že editor kontroluje text postupně od začátku do konce a mění jen chybně zarovnané segmenty).</li>
	<li>V módech automatického stránkování (s přídavkem "roll") InterText navíc při každé změně posune začátek stránky blíže k pozici aktuální změny. Číslo v závorce u módu značí, kolik pozic <em>před</em> danou pozicí se ještě na nové stránce zobrazí (pět, dvě nebo žádná (tj. jen aktuální)). Aktuální pozice změny (kurzor) se navíc zvýrazní červenou barvou, aby byla po překreslení stránky rychle k nalezení.</li>
</ul> Výchozí mód editoru je mód automatické aktualizace stavu a stránkování se dvěma předchozími pozicemi - to znamená, že při každém zásahu do zarovnání se stav všech pozic až po dané místo nastaví automaticky na "schválený" a navíc se začátek stránky přesune na pozici o dvě předcházející řádky nižší, než kde byla právě provedena změna. Aktuálně změněná pozice bude tedy nově třetí odshora na stránce a bude červeně zvýrazněna.</li>
<li><em>Přepínač počtu pozic na stránku</em> umožňuje zvolit, kolik pozic (segmentů / řádek) se současně zobrazí na jedné stránce editoru. Výchozí hodnota je 20. Zvolit lze také 10, 50 nebo 100 pozic.</li>
<li>Symbol záložek se šipkami vpřed a vzad (<img src="icons/go-prev.png" alt="&lt;"/><img src="icons/mark.png"/><img src="icons/go-next.png" alt="&gt;"/>) umožňuje rychle se přesouvat dopředu a dozadu mezi pozicemi označenými záložkami (značkami) v druhém sloupci za číslem pozice. Kliknutí na šipku přesune okno (kurzor) na nejbližší označenou pozici ve zvoleném směru.</li>
<li>Symbol dalekohledu <img src="icons/search.png" alt="search"/> otevře v dolní části lištu pro vyhledávání v textu. Více viz sekce <a href="#searchbar">vyhledávání v textu</a>.</li>
<li>Symbol šipky s tečkou <img src="icons/go-jump.png" alt="goto"/> umožňuje rychlý skok na libovolnou pozici v zarovnání, jejíž číslo je možné zadat v dialogovém okně, které se objeví po kliknutí na symbol.</li>
<li>Ikona textu s lupou <img src="icons/to-check.png" alt="skip to unchecked" /> umožňuje rychle přeskočit na první neschválenou pozici v textu (tedy první segment se stavem jiným než "schváleno"). Lze se tak jedním kliknutím dostat na místo, kde byla naposledy editace (kontrola) ukončena.</li>
<?php if ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) { ?>
<li>Ikona žurnálu <img src="icons/journal.png" alt="[j]" /> se zobrazuje pouze pokud je k dispozici historie změn v dotyčném zarovnání (nesouvisí se změnami obsahu textů!). Její pomocí lze otevřít chronologický výpis této historie (viz <a href="#journal">níže</a>).</li>
<?php } ?>
</ul>

<h3>Dolní ovládací lišta editoru</h3>

<p>Dolní lišta obsahuje na pravém a levém okraji modré šipky ke stránkování na předchozí a následující stránku v textu, stejně jako horní lišta. Navíc se v jejím středu nachází speciální tlačítko, jímž je možné současně schválit všechny segmenty na aktuální stránce (tj. nastavit jim stav na "schváleno") a přesunout se na další stránku. Toto tlačítko lze výhodně použít pokud na celé stránce nebylo třeba provádět žádné změny a lze pokračovat v kontrole další stránky.</p>

<h3>Stavová řádka</h3>

<p>Stavová řádka ukazuje přibližnou aktuální pozici v textu, přepočítanou na stránky a procenta. Ukazuje též souhrnný počet všech pozic (linků) v zarovnání.</p>

<a name="searchbar"></a>
<h3>Vyhledávání v textu</h3>

<p>Vyhledávání v textu je možné pomocí hledací lišty, která se aktivuje a deaktivuje kliknutím na symbol <img src="icons/search.png" alt="search"/> na <a href="#topbar">horní liště</a>. Lze zde vybrat mód vyhledávání, zadat textový řetězec, který má být hledán, a vybrat, zda se má hledat v textové verzi na levé ("left") či pravé ("right") straně nebo na obou stranách ("both sides"). Pomocí šipek vlevo a vpravo lze pak přeskakovat na následující a předcházející pozice, kde se hledaný text vyskytuje. Pokud hledání dospěje na konec, začne se automaticky hledat opět od začátku. (Totéž platí i v opačném směru, kdy se začne po dosažení začátku hledat od konce textu.)</p>

<p>Aktuální módy vyhledávání:</p>
<ul>
<li>Vyhledávání zadaného řetězce ("substring") může být buď necitlivé na velikost písmen ani diakritiku ("insensitive") nebo naopak musí řetězec odpovídat přesně ("exact"), včetně velikosti písmen. Hledat lze takto části slov nebo celá (přesná) sousloví v textu.</li>
<?php if (!$DISABLE_FULLTEXT) { ?>
<li>Vyhledávání fulltextové ("fulltext") je vždy necitlivé na velikost písmen i diakritku. Umožňuje hledat celá slova a jejich libovolné kombinace. (POZOR: lze hledat pouze slova o minimální délce 4 znaků!) Volba "all words" bude automaticky hledat věty obsahující všechna zadaná slova najednou, bez ohledu na jejich pořadí či rozmístění ve větě. (Hledání se vztahuje pouze na rámec jednotlivých vět, nikoliv na celé segmenty. Hledat lze pochopitelně pouze konkrétní slovní tvary, nikoliv lemmata.)</li>
<li>Vlastní fulltextové vyhledávání ("custom") umožňuje zadat složitější dotazy. Znaménkem plus (+) je třeba předznačit všechna slova, která musí být v hledané větě přítomna, znaménkem mínus (-) lze naopak označit slova, která nesmí být v téže větě přítomna. Slova bez znaménka budou brána jako volitelná, tj. stačí když bude ve větě přítomno jen jedno z nich. (Příklad: dotaz "+chytit +pačesy -příležitost" bude hledat věty, které obsahují slova "chytit" a "pačesy", ale neobsahují slovo "příležitost".) Toto hledání má stejná omezení jako výše popsané fulltextové hledání, které pouze automaticky přidává ke všem zadaným slovům znaménko '+'. Bližší podrobnosti viz <a href="http://dev.mysql.com/doc/refman/5.1/en/fulltext-boolean.html" target="_blank">manuál MySQL: "Boolean Full-text Searches"</a>.</li>
<?php } ?>
<li>Vyhledávání pomocí regulárních výrazů je k dispozici v podobě přesného hledání ("exact") nebo hledání částečně necitlivého na velikost písmen ("insensitive in ascii": bohužel nefunguje dobře u znaků s diakritikou - omezení databáze MySQL). Bližší podrobnosti o podobě regulárních výrazů podporovaných v MySQL viz <a href="http://dev.mysql.com/doc/refman/5.1/en/regexp.html" target="_blank">manuál</a>.</li>
<li>Vyhledávání elementů (vět) podle ID ("element ID") umožňuje hledat věty podle jejich identifikátoru (čísla).</li>
<li>Vyhledávání prázdných segmentů ("empty segment") umožňuje vyhledat pozice, na kterých na zvolené straně zcela chybí text. (Pole pro hledaný řetězec je pro toto hledání zcela irelevantní.)</li>
<li>Vyhledávání netriviálních segmentů ("non-1:1 segment") umožňuje rychle vyhledat pozice, na kterých jsou vůči sobě zarovnané elementy (věty) v jiném než přímém poměru 1:1. (Pole pro hledaný řetězec i výběr strany jsou pro toto hledání zcela irelevantní.)</li>
<li>Vyhledávání velkých segmentů ("large segments (>2:2)") umožňuje rychle vyhledat pozice, na kterých jsou vůči sobě zarovnané na obou stranách alespoň dva elementy (věty) či více. (Pole pro hledaný řetězec i výběr strany jsou pro toto hledání zcela irelevantní.)</li>
<li>Vyhledávání změněných elementů ("changed/edited elements") umožňuje rychle najít elementy, které byly změněny (tj. rozděleny, spojeny nebo jejichž text byl změněn/editován).</li>
</ul>

<p>Možnosti vyhledávání jsou omezeny aktuálními možnostmi použité databáze <a href="http://www.mysql.com" target="_blank">MySQL</a>.</p>

<?php if ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) { ?>
<a name="journal"></a>
<h3>Historie změn v zarovnání</h3>

<p>Pokud je v konfiguraci InterTextu zapnuto zaznamenávání všech změn v zarovnání textů (změny obsahu textu se zaznamenávají automaticky vždy), je možné si tuto historii nechat zobrazit kliknutím na ikonu žurnálu <img src="icons/journal.png" alt="[j]" /> v horní liště editoru. Zobrazí se stránka s chronologicky seřazenou tabulkou změn:</p>

<ul>
<li>V prvním sloupci je příslušnou ikonou (odpovídající ikoně použité šipky) vyznačena změna, která byla provedena a případně název jazykové verze textu, které se týkala (pokud ne obou).</li>
<li>Druhý sloupec ukazuje (původní!) číslo pozice, na které byla změna provedena. Kliknutím na číslo se otevře zarovnání na dotyčné pozici. Je však třeba pamatovat na to, že změnami se čísla pozic mění!</li>
<li>Třetí sloupec indikuje "rychlost", s jakou dotyčný uživatel v textu postoupil od minulé změny. Pokud je tato změna vzdálená o více než pět pozic od poslední změny, číslo zde udává rychlost postupu v počtu v pozic za minutu, za předpokladu, že editor postupuje s kontrolou lineárně. V takovém případě velmi vysoké číslo v tomto sloupci může indikovat méně kvalitní kontrolu či "přeskakování".</li>
<li>Poslední dva sloupce zobrazují jméno uživatele a datum a čas provedení změny.</li>
</ul>
<?php } ?>

<a name="textmanager"></a>
<h2>Správce textů</h2>

<p>Správce textů je přístupný pouze administrátorům. Umožňuje přidávat do systému nové texty nebo je mazat. 
<?php if ($USER['type']==$USER_ADMIN) { ?>
Tabulka se seznamem textů obsahuje v prvním sloupci název textu a v druhém sloupci seznam jeho (jazykových) verzí. Kliknutím na název textu se otevře podrobnější pohled na detaily o každé jednotlivé verzi a ikonka <img src="icons/document-new.png" alt="[ADD VERSION]"/> pro přidání nové verze textu. Kliknutím na název verze se otevře <a href="#almanager">správce zarovnání</a> s výpisem existujících zarovnání pouze pro danou textovou verzi (zde je také možné vytvořit nové zarovnání dané verze vůči jiné verzi). Kliknutím na odkaz "[all alignments]" v horní liště se zobrazí správce zarovnání se seznamem všech zarovnání.</p>

<p>Podrobný pohled na jednotlivé verze textu obsahuje tyto symboly:</p>
<ul>
<li><img src="icons/document-save.png" alt="[EXPORT]" /> Ikona diskety umožňuje export textu v podobě čistého XML dokumentu nezávislého na konkrétním zarovnání. Lze vybrat mezi obyčejnými (krátkými) identifikátory a dlouhými indetifikátory projektu InterCorp pro korpusový manažer Manatee. Jiné formy exportu jsou přístupné pouze z <a href="#aleditor">editoru konkrétního zarovnání</a> nebo pomocí CLI nástroje <em>export</em>.</li>
<li><img src="icons/format-list-ordered.png" alt="[UPDATE]" /> Ikonka pro ruční aktualizaci identifikátorů elementů v textu, jehož struktura (tj. dělení vět) byla změněna. Všem zarovnávatelným elementům bude přidělen dvojúrovňový identifikátor v podobě dvojmístného čísla, odděleného dvojtečkou: první část určuje pořadové číslo mateřského elementu (kontejneru, např. odstavce), druhé číslo pak pořadové číslo zarovnávatelného elementu v jeho mateřském elementu. Např. <em>id="30:5"</em> bude nastaven páté větě ve třicátém odstavci. Jako první jsou tudíž aktualizovány identifikátory mateřských elementů (kontejnerů), které jsou jen jednomístnými čísly (bez dvojtečky). (Tato operace je automaticky samočinně spouštěna při každém pokusu o export dané textové verze nebo jejího zarovnání, ať už ze správce textů nebo z <a href="#aleditor">editoru zarovnání</a>.) Před spuštěním akce je provedena kontrola zahnízdění mateřských elementů (kontejnerů), které by mohlo způsobit chybu v dvojúrovňovém číslování, a pokud je takové hnízdění nalezeno, je provedeno pouze jednoúrovňové přečíslování zarovnávatelných elementů a nikoliv jejich rodičů (kontejnerů).</li>
<li><img src="icons/flag-red.png" alt="no uniq ids" /> Symbol červeného praporku značí, že text nemá unikátní identifikátory u všech zarovnávatelných elementů a před exportem bude třeba je znovu vygenerovat. Pokud nebyl text už importován bez unikátních identifikátorů, znamená to, že jeho struktura (dělení vět) byla změněna.</li>
<li><img src="icons/flag-yellow.png" alt="text changed" /> Symbol žlutého praporku značí, že text byl editován (změněn) a není tedy identický s textem, který byl importován.</li>
<li><img src="icons/edit-delete-shred.png" alt="[DELETE]" /> Ikonka pro vymazání dané verze textu ze systému je přístupná pouze u textů, které nejsou součástí žádného zarovnání. Pro smazání textu je tedy třeba nejdříve smazat všechna jeho zarovnání.</li>
</ul>

<h3>Stránkování seznamu textů</h3>
<p>Seznam se objevuje po stránkách, mezi nimiž lze listovat pomocí odkazů "&lt;&lt; previous" a "next &gt;&gt;". Počet textů zobrazených na jedné stránce lze nastavit v horní liště selektorem "show". Lze volit mezi hodnotami 10, 20, 30, 50 a 100 textů na stránku, nebo vypnout stránkování a nechat si zobrazit vždy celý seznam (volba "all"). 
</p>

<a name="newtext"></a>
<h3>Přidání nového textu nebo nové textové verze</h3>

<p>Nový text lze přidat pomocí patřičné volby v menu <a href="#textmanager">správce textů</a>. Novou (jazykovou) verzi textu lze přidat pomocí ikonky <img src="icons/document-new.png" alt="[ADD VERSION]"/> v otevřeném pohledu na seznam verzí vybraného textu. Po kliknutí na některou z těchto voleb se zobrazí formulář pro import nového textu.</p>

<ul>
<li>Pokud se přidává zcela nový text, je třeba zadat nejdříve jeho název.</li>
<li>Dále je třeba zadat název aktuálně přidávané textové verze.</li>
<li>Je třeba vybrat soubor s textem pro import. Soubor musí mít podobu validního XML libovolné struktury a nesmí obsahovat nedefinované entity.</li>
<li>Je možné nastavit jména XML elementů obsahujících zarovnatelný text v daném XML dokumentu, v podobě seznamu jmen oddělených mezerou.</li>
<li>Volitelně lze aktivovat validaci podle DTD schématu.</li>
</ul>

<p>Nové texty a textové verze je též možné importovat pomocí CLI nástroje <em>import</em> (nápověda se zobrazí po spuštění skriptu bez parametrů). Tento nástroj (na rozdíl od webového rozhraní) zobrazuje také podrobné informace o případných konkrétních problémech s validitou importovaného XML dokumentu. Navíc umožňuje (na vyžádání) současně i generovat nebo importovat zarovnání vůči jiným, již importovaným verzím, automatickým voláním CLI nástroje <em>align</em>.</p>

<?php } else { ?>
</p>
<?php } ?>

<a name="usermanager"></a>
<h2>Správce uživatelů</h2>

Pomocí odkazu "[users]" v menu může administrátor otevřít jednoduchého správce uživatelů (zde lze přidávat, editovat nebo mazat uživatele). Ostatní uživatelé si zde mohou změnit své heslo. 

</div>
</body>
</html>

<?php

$baseDir = 'images';

if (!empty($_REQUEST['dir'])) {
    $baseDir .= '/'.$_REQUEST['dir'];
}

$srcDir = "$baseDir/unsorted";

$isAjax = isset($_SERVER['HTTP_ORIGIN']);

function dirCount($path) {
    $di = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
    return iterator_count($di);
}

function getFile($path) {
    if ($handle = opendir($path)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry[0] === '.')
                continue;

            $entryPath = rtrim($path, '/').'/'.$entry;

            if (is_dir($entryPath)) {
                $entryPath = getFile($entryPath);
                if (!$entryPath)
                    continue;
            }

            return $entryPath;
        }
    }
}

function getDirs($path) {
    return glob("$path/*", GLOB_ONLYDIR);
}

if (isset($_POST['dest'])) {
    if (!file_exists($_POST['dest'])) {
        mkdir($_POST['dest']);
    }
    $dest = $_POST['dest'].'/'.basename($_POST['src']);
    rename($_POST['src'], $dest);
    if ($isAjax) {
        $next = getFile($srcDir);
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(['dest' => $dest, 'next' => $next]);
    } else {
        header('Location: .');
    }
    die();
}

$count = dirCount($srcDir);
$image = getFile($srcDir);
$dirs = getDirs($baseDir);
?>

<meta name="viewport" content="width=device-width, initial-scale=1">

<form class="wrap" id="form" method="post">
    <input type="hidden" name="dir" value="<?=@$_REQUEST['dir']?>">
    <div class="imageWrap">
        <img id="image">
    </div>
    <div class="status">    
        <div>
            <input type="text" readonly name="src" value="<?=$image?>" size="9">
            <span id="loading">Wait...</span>
        </div>
        <div>
            <small>
                <span id="count">1</span>/<?=$count?>
            </small>
            <button id="prev" type="button" disabled>Prev</button>
            <button id="next" type="button" disabled>Next</button>
        </div>
    </div>
    <div class="buttons">
        <?php foreach ($dirs as $i => $dir) if ($dir !== $srcDir): ?>
            <button type="submit" name="dest" value="<?=$dir?>/unsorted" style="background: hsl(<?=($i % 8) * 51 ?>, 70%, 90%)"><?=basename($dir)?></button>
        <?php endif ?>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const image = document.querySelector('#image');
        const loading = document.querySelector('#loading');
        const count = document.querySelector('#count');
        const form = document.querySelector('#form');
        const prev = document.querySelector('#prev');
        const next = document.querySelector('#next');

        const history = [];
        let index = 0;

        image.src = <?=json_encode($image)?>;

        image.addEventListener('load', () => {
            loading.hidden = true;
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            if (loading.hidden === false)
                return;

            loading.hidden = false;
            next.disabled = true;
            prev.disabled = true;

            const body = new FormData(form);
            if (e.submitter && e.submitter.name) {
                body.append(e.submitter.name, e.submitter.value);
            }
            
            fetch('.', {method: 'POST', body})
                .then((response) => response.json())
                .then((result) => {
                    form.elements.src.value = image.src = result.next;
                    if (index === history.length) {
                        index = history.push(result.dest);
                    } else {
                        history[index] = result.dest;
                    }
                    updateState();
                });
        });

        prev.addEventListener('click', (e) => {
            form.elements.src.value = image.src = history[--index];
            updateState();
        });

        next.addEventListener('click', (e) => {
            form.elements.src.value = image.src = history[++index];
            updateState();
        });

        function updateState() {
            count.innerText = index + 1;
            prev.disabled = index < 1;
            next.disabled = index >= history.length - 1;
        }
    });
</script>

<style>
    body {
        overflow: hidden;
        margin: 0;
        height: 100%;
    }
    .wrap {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .wrap > * {
        flex: 0 0 auto;
    }
    .imageWrap {
        flex: 1 1 auto;
        height: 0;
        display: flex;
        background: 
            linear-gradient(135deg, transparent 75%, rgba(255, 255, 255, .4) 0%) 0 0,
            linear-gradient(-45deg, transparent 75%, rgba(255, 255, 255, .4) 0%) 15px 15px,
            linear-gradient(135deg, transparent 75%, rgba(255, 255, 255, .4) 0%) 15px 15px,
            linear-gradient(-45deg, transparent 75%, rgba(255, 255, 255, .4) 0%) 0 0,
            #ddd;
        background-size: 30px 30px;
    }
    .imageWrap img {
        margin: auto;
        max-width: 100%;
        max-height: 100%;
    }
    .status {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px;
        white-space: nowrap;
    }
    .status button {
        user-select: none;
        padding: 8px;
        background: #ccc;
        border: 1px solid #999;
        border-radius: 2px;
    }
    .buttons {
        display: flex;
        flex-wrap: wrap;
    }
    .buttons button {
        user-select: none;
        box-sizing: border-box;
        flex: 1 0 50%;
        margin: 0;
        padding: 16px 8px;
        font-size: 15px;
        border: none;
        border-top: 1px solid rgba(0, 0, 0, .2);
        opacity: .9;
    }
    .buttons button:nth-of-type(2n) {
        border-left: 1px solid rgba(0, 0, 0, .2);
    }
    .buttons button:active {
        color: green;
        opacity: 1;
        box-shadow: none;
        outline: none;
    }
    @media (min-height: 800px) {
        .buttons button {
            padding: 24px 8px;
        }
    }
</style>

{extends file='page.tpl'}
{block name="page_content"}
    <style>
        .page-content{
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
        }

        .extend-btn{
            border-radius: 40px;
        }

        hr{
            margin-top: unset;
        }
    </style>
        <h3>Nous sommes ravis de vous revoir !</h3>
        <hr size='4' style='width:100%'>
        <p>Bonjour ! Votre dernière connection remonte à plus de 2 ans, voulez-vous réactiver votre compte ou le supprimer ?</p>
        <form method='post' enctype="multipart/form-data">
            <button class='btn btn-info extend-btn' type='submit' value='reactiver' name='reactiver'>Réactiver mon compte</button>
            <button class='btn btn-danger extend-btn' type='submit' value='supprimer' name='supprimer'>Supprimer mon compte</button>
        </form>
{/block}



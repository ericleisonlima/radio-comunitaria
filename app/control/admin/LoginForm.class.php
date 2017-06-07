<?php

class LoginForm extends TPage
{
    protected $form;

    function __construct($param)
    {
        parent::__construct();

        $table = new TTable;
        $table->style = 'width:100%; border-radius:10px; box-shadow:10px 10px 5px #ddd;';

        // creates the form
        $this->form = new TForm('form_login');
        $this->form->class = 'tform';
        $this->form->style = 'max-width:350px; margin:auto; margin-top:160px;';

        // add the notebook inside the form
        $this->form->add($table);

        // create the form fields
        $icon = new TImage('app/images/logo.png'); //logo do site
        $login = new TEntry('login');
        $password = new TPassword('password');

        // define the sizes
        $login->setSize('63%', 40);
        $password->setSize('63%', 40);

        $icon->style = 'padding-top: 15px;';
        $login->style    = 'height:35px; margin-top:15px; font-size:14px; float:left; border-bottom-left-radius:0; border-top-left-radius:0;';
        $password->style = 'height:35px; margin-bottom:15px; font-size:14px; float:left; border-bottom-left-radius:0; border-top-left-radius:0;';

        $login->placeholder = _t('User');
        $password->placeholder = _t('Password');

        $user   = '<span style="float:left; width:35px; margin-top:15px; margin-left:45px; height:35px;" class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>';
        $locker = '<span style="float:left; width:35px; margin-left:45px; height:35px;" class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>';

        $container1 = new TElement('div');
        $container1->add($user);
        $container1->add($login);

        $container2 = new TElement('div');
        $container2->add($locker);
        $container2->add($password);


        $row = $table->addRow();
        $row->addCell( $icon )->colspan = 2;
        $row->style = 'text-align:center;';

        $row = $table->addRow();
        $row->addCell($container1)->colspan = 2;

        // add a row for the field password
        $row = $table->addRow();
        $row->addCell($container2)->colspan = 2;

        // create an action button (save)
        $login_button=new TButton('save');
        // define the button action
        $login_button->setAction(new TAction(array($this, 'onLogin')), 'Clique para entrar' );
        $login_button->class = 'btn btn-success btn-hover';
        $login_button->style = 'font-size:18px;width:90%;padding:10px;';

        $row = $table->addRow();
        $row->class = 'tformaction';
        $cell = $row->addCell( $login_button );
        $cell->colspan = 2;
        $cell->style = 'text-align:center; border-radius:0 0 10px 10px;';

        $this->form->setFields(array($login, $password, $login_button));

        // add the form to the page
        parent::add($this->form);
    }

    public function onLogin()
    {
        try
        {
            TTransaction::open('dbsic');
            $data = $this->form->getData('StdClass');
            $this->form->validate();
            $user = SystemUser::authenticate( $data->login, $data->password );
            if ($user)
            {
                TSession::regenerate();
                $programs = $user->getPrograms();
                $programs['LoginForm'] = TRUE;

                TSession::setValue('logged', TRUE);
                TSession::setValue('login', $data->login);
                TSession::setValue('userid', $user->id);
                TSession::setValue('usergroupids', $user->getSystemUserGroupIds());
                TSession::setValue('username', $user->name);
                TSession::setValue('frontpage', '');
                TSession::setValue('programs',$programs);
                TSession::setValue('medico_id',$user->medico_id);

                if (!empty($user->unit))
                {
                    TSession::setValue('userunitid',$user->unit->id);
                }

                $frontpage = $user->frontpage;
                SystemAccessLog::registerLogin();
                if ($frontpage instanceof SystemProgram AND $frontpage->controller)
                {
                    AdiantiCoreApplication::gotoPage($frontpage->controller); // reload
                    TSession::setValue('frontpage', $frontpage->controller);
                }
                else
                {
                    AdiantiCoreApplication::gotoPage('EmptyPage'); // reload
                    TSession::setValue('frontpage', 'EmptyPage');
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage());
            TSession::setValue('logged', FALSE);
            TTransaction::rollback();
        }
    }

    public static function reloadPermissions()
    {
        try
        {
            TTransaction::open('dbsic');
            $user = SystemUser::newFromLogin( TSession::getValue('login') );
            if ($user)
            {
                $programs = $user->getPrograms();
                $programs['LoginForm'] = TRUE;
                TSession::setValue('programs', $programs);

                $frontpage = $user->frontpage;
                if ($frontpage instanceof SystemProgram AND $frontpage->controller)
                {
                    TApplication::gotoPage($frontpage->controller); // reload
                }
                else
                {
                    TApplication::gotoPage('EmptyPage'); // reload
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onLogout()
    {
        SystemAccessLog::registerLogout();
        TSession::freeSession();
        AdiantiCoreApplication::gotoPage('LoginForm', '');
    }
}

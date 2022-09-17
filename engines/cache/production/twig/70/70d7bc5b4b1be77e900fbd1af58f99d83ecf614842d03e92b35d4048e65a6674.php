<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* @phpbb_viglink/event/acp_help_phpbb_stats_before.html */
class __TwigTemplate_4784609d986be3adc38537e550c23b9d9db87ddf521aa1364fcc454ba8c9c839 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        if (($context["S_VIGLINK_ASK_ADMIN"] ?? null)) {
            // line 2
            echo "\t<p>";
            echo $this->extensions['phpbb\template\twig\extension']->lang("ACP_VIGLINK_SUPPORT_EXPLAIN");
            echo "</p>
";
        }
    }

    public function getTemplateName()
    {
        return "@phpbb_viglink/event/acp_help_phpbb_stats_before.html";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "@phpbb_viglink/event/acp_help_phpbb_stats_before.html", "");
    }
}

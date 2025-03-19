from django.http import HttpResponse
from django.template.loader import render_to_string
from django.views import View

class HyperviewResponse(HttpResponse):
    """Custom HTTP response for Hyperview XML."""
    def __init__(self, xml_content, **kwargs):
        kwargs.setdefault('content_type', 'application/xml')
        super().__init__(xml_content, **kwargs)

class HyperviewView(View):
    """Base view for serving Hyperview XML content."""
    template_name = None
    
    def get_context_data(self, **kwargs):
        """Get context data for the template."""
        return kwargs
    
    def render_to_response(self, context):
        """Render the template to a Hyperview response."""
        content = render_to_string(self.template_name, context)
        return HyperviewResponse(content)
    
    def get(self, request, *args, **kwargs):
        """Handle GET requests."""
        context = self.get_context_data(**kwargs)
        return self.render_to_response(context)

class PollHyperviewView(HyperviewView):
    """View that serves poll content in Hyperview XML format."""
    template_name = 'hyperview/poll.xml'
    
    def get_context_data(self, **kwargs):
        from .models import Poll
        
        context = super().get_context_data(**kwargs)
        
        # Get the active poll
        try:
            poll = Poll.objects.filter(is_active=True).latest('created_at')
            context['poll'] = poll
            context['choices'] = poll.choices.all()
        except Poll.DoesNotExist:
            context['poll'] = None
            
        return context 
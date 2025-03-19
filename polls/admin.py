from django.contrib import admin
from .models import Poll, Choice

class ChoiceInline(admin.TabularInline):
    model = Choice
    extra = 3

@admin.register(Poll)
class PollAdmin(admin.ModelAdmin):
    list_display = ('question', 'is_active', 'created_at', 'start_time', 'end_time')
    list_filter = ('is_active', 'created_at')
    search_fields = ('question', 'description')
    inlines = [ChoiceInline]
    
    actions = ['activate_polls', 'deactivate_polls']
    
    def activate_polls(self, request, queryset):
        queryset.update(is_active=True)
        self.message_user(request, f"{queryset.count()} polls were successfully activated.")
    activate_polls.short_description = "Activate selected polls"
    
    def deactivate_polls(self, request, queryset):
        queryset.update(is_active=False)
        self.message_user(request, f"{queryset.count()} polls were successfully deactivated.")
    deactivate_polls.short_description = "Deactivate selected polls"

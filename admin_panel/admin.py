from django.contrib import admin
from .models import Performance, Story

class StoryInline(admin.TabularInline):
    model = Story
    extra = 1

@admin.register(Performance)
class PerformanceAdmin(admin.ModelAdmin):
    list_display = ('title', 'date', 'time', 'is_active')
    list_filter = ('is_active', 'date')
    search_fields = ('title', 'description')
    inlines = [StoryInline]
    
    actions = ['activate_performances', 'deactivate_performances']
    
    def activate_performances(self, request, queryset):
        queryset.update(is_active=True)
        self.message_user(request, f"{queryset.count()} performances were successfully activated.")
    activate_performances.short_description = "Activate selected performances"
    
    def deactivate_performances(self, request, queryset):
        queryset.update(is_active=False)
        self.message_user(request, f"{queryset.count()} performances were successfully deactivated.")
    deactivate_performances.short_description = "Deactivate selected performances"

@admin.register(Story)
class StoryAdmin(admin.ModelAdmin):
    list_display = ('title', 'performance', 'created_at')
    list_filter = ('performance', 'created_at')
    search_fields = ('title', 'description')

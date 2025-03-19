from django.urls import path, include
from rest_framework.routers import DefaultRouter
from . import views
from .hyperview import PollHyperviewView

router = DefaultRouter()
router.register(r'polls', views.PollViewSet)

urlpatterns = [
    path('', include(router.urls)),
    path('active-poll/', views.ActivePollView.as_view(), name='active-poll'),
    path('hyperview/poll/', PollHyperviewView.as_view(), name='hyperview-poll'),
] 